<?php

namespace ETS\PurchaseBundle\Tests\Plugin;


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

use ETS\Payment\DotpayBundle\Plugin\DotpayDirectPlugin;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use Prophecy\Argument;

/**
 * Dotpay payment plugin
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class DotpayDirectPluginTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->router = $this->prophesize('Symfony\Component\Routing\Router');
        $this->token = $this->prophesize('ETS\Payment\DotpayBundle\Client\Token');
        $this->stringNormalizer = $this->prophesize('ETS\Payment\DotpayBundle\Tools\StringNormalizer');
        $url = 'urlTest';
        $type = 1;
        $returnUrl = 'returnUrlTest';

        $this->dotpayDirectPlugin = new DotpayDirectPlugin(
            $this->router->reveal(),
            $this->token->reveal(),
            $this->stringNormalizer->reveal(),
            $url,
            $type,
            $returnUrl
        );
    }

    public function testSuccess()
    {
        $t_status = DotpayDirectPlugin::STATUS_NEW;
        $t_id = 1;
        $amount = 10;

        $extendedData = $this->prophesize('JMS\Payment\CoreBundle\Entity\ExtendedData');
        $extendedData->has('t_status')->shouldBeCalled()->willReturn($t_status);
        $extendedData->has('t_id')->shouldBeCalled()->willReturn($t_id);
        $extendedData->has('amount')->shouldBeCalled()->willReturn($amount);
        $extendedData->get('t_status')->shouldBeCalled()->willReturn($t_status);
        $extendedData->get('t_id')->shouldBeCalled()->willReturn($t_id);
        $extendedData->get('amount')->shouldBeCalled()->willReturn($amount);

        $financialTransaction = $this->prophesize('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface');
        $financialTransaction->getExtendedData()->shouldBeCalled()->willReturn($extendedData->reveal());
        $financialTransaction->getState()->shouldBeCalled()->willReturn(FinancialTransactionInterface::STATE_PENDING);
        $financialTransaction->setReferenceNumber($t_id)->shouldBeCalled();
        $financialTransaction->setProcessedAmount($amount)->shouldBeCalled();
        $financialTransaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS)->shouldBeCalled();
        $financialTransaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS)->shouldBeCalled();

        $this->dotpayDirectPlugin->approveAndDeposit($financialTransaction->reveal(), false);
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException
     */
    public function testExceptionNotNew()
    {
        $extendedData = $this->prophesize('JMS\Payment\CoreBundle\Entity\ExtendedData');
        $paymentInstruction = $this->prophesize('JMS\Payment\CoreBundle\Entity\PaymentInstruction');
        $payment = $this->prophesize('JMS\Payment\CoreBundle\Entity\Payment');
        $payment->getPaymentInstruction()->shouldbeCalled()->willReturn($paymentInstruction->reveal());
        $paymentInstruction->getId()->shouldBeCalled()->willReturn(15);
        $paymentInstruction->getCurrency()->shouldBeCalled();

        $financialTransaction = $this->prophesize('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface');
        $financialTransaction->getState()->shouldBeCalled()->willReturn(FinancialTransactionInterface::STATE_NEW);
        $financialTransaction->getPayment()->shouldBeCalled()->willReturn($payment->reveal());
        $financialTransaction->getExtendedData()->shouldBeCalled()->willReturn($extendedData->reveal());
        $financialTransaction->getRequestedAmount()->shouldBeCalled();

        $this->dotpayDirectPlugin->approveAndDeposit($financialTransaction->reveal(), false);
    }

    public function testApproveSuccess()
    {
        $t_status = DotpayDirectPlugin::STATUS_NEW;
        $t_id = 1;
        $amount = 10;

        $extendedData = $this->prophesize('JMS\Payment\CoreBundle\Entity\ExtendedData');
        $extendedData->has('t_status')->shouldBeCalled()->willReturn($t_status);
        $extendedData->has('t_id')->shouldBeCalled()->willReturn($t_id);
        $extendedData->has('amount')->shouldBeCalled()->willReturn($amount);
        $extendedData->get('t_status')->shouldBeCalled()->willReturn($t_status);
        $extendedData->get('t_id')->shouldBeCalled()->willReturn($t_id);
        $extendedData->get('amount')->shouldBeCalled()->willReturn($amount);

        $financialTransaction = $this->prophesize('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface');
        $financialTransaction->getExtendedData()->shouldBeCalled()->willReturn($extendedData->reveal());
        $financialTransaction->setReferenceNumber($t_id)->shouldBeCalled();
        $financialTransaction->setProcessedAmount($amount)->shouldBeCalled();
        $financialTransaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS)->shouldBeCalled();
        $financialTransaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS)->shouldBeCalled();


        $this->dotpayDirectPlugin->approve($financialTransaction->reveal(), false);
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\Plugin\Exception\BlockedException
     */
    public function testApproveExceptionBadData()
    {
        $extendedData = $this->prophesize('JMS\Payment\CoreBundle\Entity\ExtendedData');

        $financialTransaction = $this->prophesize('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface');
        $financialTransaction->getExtendedData()->shouldBeCalled()->willReturn($extendedData->reveal());
        $financialTransaction->setReferenceNumber(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setProcessedAmount(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setResponseCode(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setReasonCode(Argument::any())->shouldNotBeCalled();


        $this->dotpayDirectPlugin->approve($financialTransaction->reveal(), false);
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     * @expectedExceptionMessage Payment status unknow: 6
     */
    public function testApproveExceptionBadStatus()
    {
        $t_status = 6;
        $t_id = 1;
        $amount = 10;

        $extendedData = $this->prophesize('JMS\Payment\CoreBundle\Entity\ExtendedData');
        $extendedData->has('t_status')->shouldBeCalled()->willReturn($t_status);
        $extendedData->has('t_id')->shouldBeCalled()->willReturn($t_id);
        $extendedData->has('amount')->shouldBeCalled()->willReturn($amount);
        $extendedData->get('t_status')->shouldBeCalled()->willReturn($t_status);

        $financialTransaction = $this->prophesize('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface');
        $financialTransaction->getExtendedData()->shouldBeCalled()->willReturn($extendedData->reveal());
        $financialTransaction->setReferenceNumber(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setProcessedAmount(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setReasonCode(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setResponseCode('Unknown')->shouldBeCalled();

        $this->dotpayDirectPlugin->approve($financialTransaction->reveal(), false);
    }

    public function testDepositSuccess()
    {
        $t_status = DotpayDirectPlugin::STATUS_NEW;
        $t_id = 1;
        $amount = 10;

        $extendedData = $this->prophesize('JMS\Payment\CoreBundle\Entity\ExtendedData');
        $extendedData->has('t_status')->shouldBeCalled()->willReturn($t_status);
        $extendedData->has('t_id')->shouldBeCalled()->willReturn($t_id);
        $extendedData->has('amount')->shouldBeCalled()->willReturn($amount);
        $extendedData->get('t_status')->shouldBeCalled()->willReturn($t_status);
        $extendedData->get('t_id')->shouldBeCalled()->willReturn($t_id);
        $extendedData->get('amount')->shouldBeCalled()->willReturn($amount);

        $financialTransaction = $this->prophesize('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface');
        $financialTransaction->getExtendedData()->shouldBeCalled()->willReturn($extendedData->reveal());
        $financialTransaction->setReferenceNumber($t_id)->shouldBeCalled();
        $financialTransaction->setProcessedAmount($amount)->shouldBeCalled();
        $financialTransaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS)->shouldBeCalled();
        $financialTransaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS)->shouldBeCalled();


        $this->dotpayDirectPlugin->deposit($financialTransaction->reveal(), false);
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     * @expectedExceptionMessage PaymentAction rejected.
     */
    public function testDepositExceptionRejected()
    {
        $t_status = DotpayDirectPlugin::STATUS_REJECTED;
        $t_id = 1;
        $amount = 10;

        $extendedData = $this->prophesize('JMS\Payment\CoreBundle\Entity\ExtendedData');
        $extendedData->has('t_status')->shouldBeCalled()->willReturn($t_status);
        $extendedData->has('t_id')->shouldBeCalled()->willReturn($t_id);
        $extendedData->has('amount')->shouldBeCalled()->willReturn($amount);
        $extendedData->get('t_status')->shouldBeCalled()->willReturn($t_status);

        $financialTransaction = $this->prophesize('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface');
        $financialTransaction->getExtendedData()->shouldBeCalled()->willReturn($extendedData->reveal());
        $financialTransaction->setReferenceNumber(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setProcessedAmount(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setReasonCode(PluginInterface::REASON_CODE_BLOCKED)->shouldBeCalled();
        $financialTransaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS)->shouldBeCalled();

        $this->dotpayDirectPlugin->deposit($financialTransaction->reveal(), false);
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException
     * @expectedExceptionMessage reverseDeposit() is not supported by this plugin.
     */
    public function testDepositExceptionRefound()
    {
        $t_status = DotpayDirectPlugin::STATUS_REFUND;
        $t_id = 1;
        $amount = 10;

        $extendedData = $this->prophesize('JMS\Payment\CoreBundle\Entity\ExtendedData');
        $extendedData->has('t_status')->shouldBeCalled()->willReturn($t_status);
        $extendedData->has('t_id')->shouldBeCalled()->willReturn($t_id);
        $extendedData->has('amount')->shouldBeCalled()->willReturn($amount);
        $extendedData->get('t_status')->shouldBeCalled()->willReturn($t_status);

        $financialTransaction = $this->prophesize('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface');
        $financialTransaction->getExtendedData()->shouldBeCalled()->willReturn($extendedData->reveal());
        $financialTransaction->setReferenceNumber(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setProcessedAmount(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setReasonCode(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setResponseCode(Argument::any())->shouldNotBeCalled();

        $this->dotpayDirectPlugin->deposit($financialTransaction->reveal(), false);
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     * @expectedExceptionMessage Payment status unknow: 6
     */
    public function testDepositExceptionBadStatus()
    {
        $t_status = 6;
        $t_id = 1;
        $amount = 10;

        $extendedData = $this->prophesize('JMS\Payment\CoreBundle\Entity\ExtendedData');
        $extendedData->has('t_status')->shouldBeCalled()->willReturn($t_status);
        $extendedData->has('t_id')->shouldBeCalled()->willReturn($t_id);
        $extendedData->has('amount')->shouldBeCalled()->willReturn($amount);
        $extendedData->get('t_status')->shouldBeCalled()->willReturn($t_status);

        $financialTransaction = $this->prophesize('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface');
        $financialTransaction->getExtendedData()->shouldBeCalled()->willReturn($extendedData->reveal());
        $financialTransaction->setReferenceNumber(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setProcessedAmount(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setReasonCode(Argument::any())->shouldNotBeCalled();
        $financialTransaction->setResponseCode('Unknown')->shouldBeCalled();

        $this->dotpayDirectPlugin->deposit($financialTransaction->reveal(), false);
    }
}
