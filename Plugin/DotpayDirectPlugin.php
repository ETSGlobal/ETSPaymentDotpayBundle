<?php

namespace ETS\Payment\DotpayBundle\Plugin;

use Symfony\Component\Routing\Router;

use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\TimeoutException;
use JMS\Payment\CoreBundle\Plugin\ErrorBuilder;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\BlockedException;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException;

use ETS\Payment\DotpayBundle\Client\Token;
use ETS\Payment\DotpayBundle\Tools\StringNormalizer;

/*
 * Copyright 2012 ETSGlobal <ecs@etsglobal.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Dotpay payment plugin
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class DotpayDirectPlugin extends AbstractPlugin
{
    const STATUS_CLOSED = 0;
    const STATUS_NEW = 1;
    const STATUS_DONE = 2;
    const STATUS_REJECTED = 3;
    const STATUS_REFUND = 4;
    const STATUS_COMPLAINT = 5;

    public static $statuses = [
        self::STATUS_CLOSED    => 'Closed',
        self::STATUS_NEW       => 'New',
        self::STATUS_DONE      => 'Done',
        self::STATUS_REJECTED  => 'Rejected',
        self::STATUS_REFUND    => 'Refund',
        self::STATUS_COMPLAINT => 'Complaint',
    ];

    /** @var Router */
    protected $router;

    /** @var \ETS\Payment\DotpayBundle\Client\Token */
    protected $token;

    /** @var \ETS\Payment\DotpayBundle\Tools\StringNormalizer */
    protected $stringTools;

    /** @var string */
    protected $url;

    /** @var string */
    protected $returnUrl;

    /** @var integer */
    protected $type;

    /** @var bool */
    protected $chk;

    /** @var bool */
    protected $recipientChk;

    /** @var bool */
    protected $onlineTransfer;

    /** @var bool */
    protected $expirationTime;

    /** @var string */
    protected $callBackUrl;

    /**
     * @param Router            $router      The router
     * @param Token             $token       The client token
     * @param StringNormalizer  $stringTools The StringNormalizer tool package
     * @param string            $url         The urlc
     * @param int               $type        The type
     * @param string            $returnUrl   The return url
     * @param bool              $chk         Using DotPay CHK parameter, by default false
     * @param bool              $recipientChk
     * @param bool              $onlineTransfer
     * @param int               $expirationTime
     * @param string            $callBackUrl
     */
    public function __construct(
        Router $router,
        Token $token,
        StringNormalizer $stringTools,
        $url,
        $type,
        $returnUrl,
        $chk = false,
        $recipientChk = false,
        $onlineTransfer = false,
        $expirationTime = 0,
        $callBackUrl = null
    ) {
        $this->router = $router;
        $this->token = $token;
        $this->stringTools = $stringTools;
        $this->returnUrl = $returnUrl;
        $this->url = $url;
        $this->type = $type;
        $this->chk = $chk;
        $this->recipientChk = $recipientChk;
        $this->onlineTransfer = $onlineTransfer;
        $this->expirationTime = $expirationTime;
        $this->callBackUrl = $callBackUrl;
    }

    /**
     * This method executes a deposit transaction without prior approval
     * (aka "sale", or "authorization with capture" transaction).
     *
     * A typical use case for this method is an electronic check payments
     * where authorization is not supported. It can also be used to deposit
     * money in only one transaction, and thus saving processing fees for
     * another transaction.
     *
     * @param FinancialTransactionInterface $transaction The transaction
     * @param boolean                       $retry       Retry
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        if ($transaction->getState() === FinancialTransactionInterface::STATE_NEW) {
            throw $this->createDotpayRedirectActionException($transaction);
        }

        $this->approve($transaction, $retry);
        $this->deposit($transaction, $retry);
    }

    /**
     * @param FinancialTransactionInterface $transaction
     *
     * @return ActionRequiredException
     */
    public function createDotpayRedirectActionException(FinancialTransactionInterface $transaction)
    {
        $actionRequest = new ActionRequiredException('Redirecting to DotPay.');
        $actionRequest->setFinancialTransaction($transaction);

        $instruction = $transaction->getPayment()->getPaymentInstruction();

        $extendedData = $transaction->getExtendedData();
        $urlc = $this->getCallBackUrl($instruction, 'ets_payment_dotpay_callback_urlc');

        $data = [
            'id'                => $this->token->getId(),
            'URL'               => $this->getReturnUrl($extendedData),
            'URLC'              => $urlc,
            'type'              => $this->type,
            'onlinetransfer'    => $this->onlineTransfer ? 1 : 0,
            'amount'            => $transaction->getRequestedAmount(),
            'currency'          => $instruction->getCurrency(),
            'description'       => sprintf('Payment Instruction #%d', $instruction->getId()),
            'data_waznosci'     => $this->expirationTime > 0 ? date('Y-m-d H:i:s', time() + $this->expirationTime * 60) : null,
            'data_zapadalnosci' => null,
        ];

        $additionalData = [
            'street', 'phone', 'postcode', 'lastname', 'firstname',
            'email', 'country', 'city', 'grupykanalow', 'street_n1', 'street_n2', 'description',
        ];

        if ($this->recipientChk) {
            $additionalData = array_merge($additionalData, [
                'recipientAccountNumber', 'recipientCompany', 'recipientFirstName', 'recipientLastName', 'recipientAddressStreet',
                'recipientAddressBuilding', 'recipientAddressApartment', 'recipientAddressPostcode', 'recipientAddressCity',
            ]);
        }

        foreach ($additionalData as $value) {
            if ($extendedData->has($value)) {
                $data[$value] = $this->stringTools->normalize($extendedData->get($value));
            }
        }

        if ($extendedData->has('lang')) {
            $data['lang'] = substr($extendedData->get('lang'), 0, 2);
        }

        if ($this->recipientChk) {
            $data['recipientChk'] = $this->generateRecipientChk($data, $this->token->getPin());
        }

        if ($this->chk) {
            $data['chk'] = $this->generateChk($data, $this->token->getPin());
        }

        $actionRequest->setAction(new VisitUrl($this->url . '?' . http_build_query($data)));

        return $actionRequest;
    }

    /**
     * This method generates chk parameter user to sign request to dotpay
     *
     * @param array $data
     * @param string $pin
     *
     * @return string
     */
    protected function generateChk(array $data, $pin)
    {
        $key =  $data['id'];
        $key .= number_format($data['amount'], 2, '.', '');
        $key .= $data['currency'];
        $key .= rawurlencode($data['description']);

        if (isset($data['control'])) {
            $key .= $data['control'];
        }

        $key .= $pin;

        if (isset($data['channel'])) {
            $key .= $data['channel'];

            if (isset($data['chlock'])) {
                $key .= $data['chlock'];
            }
        }

        if (isset($data['data_waznosci'])) {
            if (isset($data['data_zapadalnosci'])) {
                $key .= $data['data_zapadalnosci'];
            }

            $key .= $data['data_waznosci'];
        }

        if (isset($data['recipientChk'])) {
            $key .= $data['recipientChk'];
        }

        return md5($key);
    }

    /**
     * This method generates recipientChk parameter user to sign request with recipient data to dotpay
     *
     * @param array $data
     * @param string $pin
     *
     * @return string
     */
    protected function generateRecipientChk(array $data, $pin)
    {
        $key =  $data['id'];
        $key .= number_format($data['amount'], 2, '.', '');
        $key .= $data['currency'];

        if (isset($data['control'])) {
            $key .= $data['control'];
        }

        $recipientFields = [
            'recipientAccountNumber',
            'recipientCompany',
            'recipientFirstName',
            'recipientLastName',
            'recipientAddressStreet',
            'recipientAddressBuilding',
            'recipientAddressApartment',
            'recipientAddressPostcode',
            'recipientAddressCity',
        ];

        foreach ($recipientFields as $f) {
            if (isset($data[$f])) {
                $key .= $data[$f];
            }
        }

        $key .= $pin;

        return hash('sha256', $key);
    }

    /**
     * This method executes an approve transaction.
     *
     * By an approval, funds are reserved but no actual money is transferred. A
     * subsequent deposit transaction must be performed to actually transfer the
     * money.
     *
     * A typical use case, would be Credit Card payments where funds are first
     * authorized.
     *
     * @param FinancialTransactionInterface $transaction The transaction
     * @param boolean                       $retry       Retry
     */
    public function approve(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();

        $this->checkExtendedDataBeforeApproveAndDeposit($data);

        switch ($data->get('t_status')) {
            case self::STATUS_NEW:
                // TODO: The status should not be NEW at this point, I think
                // we should throw an Exception that trigger the PENDING state
            case self::STATUS_COMPLAINT:
                // TODO: What is this status ? should we deal with it ?
            case self::STATUS_DONE:
            case self::STATUS_CLOSED:
            case self::STATUS_REFUND:
            case self::STATUS_REJECTED:
                break;

            default:
                $ex = new FinancialException('Payment status unknow: '.$data->get('t_status'));
                $ex->setFinancialTransaction($transaction);
                $transaction->setResponseCode('Unknown');

                throw $ex;
        }

        $transaction->setReferenceNumber($data->get('t_id'));
        $transaction->setProcessedAmount($data->get('amount'));
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
    }

    /**
     * This method executes a deposit transaction (aka capture transaction).
     *
     * This method requires that the Payment has already been approved in
     * a prior transaction.
     *
     * A typical use case are Credit Card payments.
     *
     * @param FinancialTransactionInterface $transaction The transaction
     * @param boolean                       $retry       Retry
     *
     * @return mixed
     */
    public function deposit(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();

        $this->checkExtendedDataBeforeApproveAndDeposit($data);

        switch ($data->get('t_status')) {
            case self::STATUS_CLOSED:
                $ex = new TimeoutException('PaymentAction closed');
                $ex->setFinancialTransaction($transaction);

                throw $ex;

            case self::STATUS_NEW:
                // TODO: The status should not be NEW at this point, I think
                // we should throw an Exception that trigger the PENDING state
            case self::STATUS_COMPLAINT:
                // TODO: What is this status ? should we deal with it ?
            case self::STATUS_DONE:
                break;

            case self::STATUS_REJECTED:
                $ex = new FinancialException('PaymentAction rejected.');
                $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_BLOCKED);
                $ex->setFinancialTransaction($transaction);

                throw $ex;

            case self::STATUS_REFUND:
                return $this->reverseDeposit($transaction, $retry);

            default:
                $ex = new FinancialException('Payment status unknow: '.$data->get('t_status'));
                $ex->setFinancialTransaction($transaction);
                $transaction->setResponseCode('Unknown');

                throw $ex;
        }

        $transaction->setReferenceNumber($data->get('t_id'));
        $transaction->setProcessedAmount($data->get('amount'));
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
    }

    /**
     * Check that the extended data contains the needed values
     * before approving and depositing the transation
     *
     * @param ExtendedData $data
     *
     * @throws BlockedException
     */
    protected function checkExtendedDataBeforeApproveAndDeposit(ExtendedData $data) {

        if (!$data->has('t_status') || !$data->has('t_id') || !$data->has('amount')) {
            // if these data are missing, we should wait the response from DotPay
            // and the transaction should stay in pending state
            throw new BlockedException("Awaiting extended data from DotPay");
        }
    }

    /**
     * This method cancels a previously approved payment.
     *
     * @param FinancialTransactionInterface $transaction The transaction
     * @param boolean                       $retry       Retry
     *
     * @throws InvalidDataException if a partial amount is passed, but this is
     *                              not supported by the payment backend system
     */
    public function reverseApproval(FinancialTransactionInterface $transaction, $retry)
    {
        throw new FunctionNotSupportedException('reverseApproval() is not supported by this plugin.');
    }

    /**
     * This method cancels a previously deposited amount.
     *
     * @param FinancialTransactionInterface $transaction The transaction
     * @param boolean                       $retry       Retry
     *
     * @throws InvalidDataException if a partial amount is passed, but this is
     *                              not supported by the payment backend system
     */
    public function reverseDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        throw new FunctionNotSupportedException('reverseDeposit() is not supported by this plugin.');
    }

    /**
     * @param PaymentInstructionInterface $paymentInstruction
     *
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\InvalidPaymentInstructionException
     */
    public function checkPaymentInstruction(PaymentInstructionInterface $paymentInstruction)
    {
        $errorBuilder = new ErrorBuilder();
        $data = $paymentInstruction->getExtendedData();

        // TODO Check requirements here

        if ($errorBuilder->hasErrors()) {
            throw $errorBuilder->getException();
        }
    }

    /**
     * @param string $paymentSystemName
     *
     * @return boolean
     */
    public function processes($paymentSystemName)
    {
        return 'dotpay_direct' === $paymentSystemName;
    }

    /**
     * @param \JMS\Payment\CoreBundle\Model\ExtendedDataInterface $data
     *
     * @return string
     * @throws \RuntimeException
     */
    protected function getReturnUrl(ExtendedDataInterface $data)
    {
        if ($data->has('return_url')) {
            $url = $data->get('return_url');
            if (!empty($url)) {
                return $url;
            }
        }

        if (0 !== strlen($this->returnUrl)) {
            return $this->returnUrl;
        }

        throw new \RuntimeException('You must configure a return url.');
    }

    private function getCallBackUrl(PaymentInstructionInterface $instruction, $route)
    {
        if ($this->callBackUrl) {
            return $this->callBackUrl.$this->router->generate(
                $route,
                ['id' => $instruction->getId()],
                Router::RELATIVE_PATH
            );
        }

        return $this->router->generate(
            $route,
            ['id' => $instruction->getId()],
            Router::ABSOLUTE_PATH
        );
    }
}
