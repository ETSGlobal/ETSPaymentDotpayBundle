<?php

namespace ETS\Payment\DotpayBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use ETS\Payment\DotpayBundle\Event\DotpayConfirmationReceivedEvent;
use ETS\Payment\DotpayBundle\Event\Events as DotpayEvents;

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
 * Callback controller
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class CallbackController extends Controller
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request         $request     The request
     * @param \JMS\Payment\CoreBundle\Entity\PaymentInstruction $instruction The payment instruction
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function urlcAction(Request $request, PaymentInstruction $instruction)
    {
        $this->get('event_dispatcher')->dispatch(
            DotpayEvents::PAYMENT_DOTPAY_CONFIRMATION_RECEIVED,
            new DotpayConfirmationReceivedEvent($instruction, $request->request)
        );

        $client = $this->get('payment.dotpay.client.token');
        $logger = $this->get('logger');
        $ppc = $this->get('payment.plugin_controller');

        $logger->info(
            '[Dotpay - URLC - {dotpayTransactionId}] Callback received: {request}',
            [
                'paymentInstructionId' => $instruction->getId(),
                'request' => $request->request->all(),
            ]
        );

        $transactionId = $request->request->get('t_id');
        $transactionStatus = $request->request->get('t_status');

        // Check the PIN
        $control = md5(sprintf(
            "%s:%s:%s:%s:%s:%s:%s:%s:%s:%s:%s",
            $client->getPin(),
            $client->getId(),
            $request->request->get('control'),
            $transactionId,
            $request->request->get('amount'),
            $request->request->get('email'),
            $request->request->get('service'),
            $request->request->get('code'),
            $request->request->get('username'),
            $request->request->get('password'),
            $transactionStatus
        ));

        if ($control !== $request->request->get('md5')) {
            $logger->error(
                '[Dotpay - URLC - {dotpayTransactionId}] pin verification failed',
                [
                    'paymentInstructionId' => $instruction->getId(),
                    'dotpayTransactionId' => $transactionId,
                    'dotpayTransactionStatus' => $transactionStatus,
                ]
            );

            return new Response('FAIL SIGNATURE', 500);
        }

        if (null === $transaction = $instruction->getPendingTransaction()) {
            // this could happen if the transaction is already validated via http redirection

            if ($instruction->getAmount() < $instruction->getDepositedAmount()) {
                $logger->error(
                    '[Dotpay - URLC - {dotpayTransactionId}] unable to create new transaction, all of amount has been deposited',
                    [
                        'paymentInstructionId' => $instruction->getId(),
                        'dotpayTransactionId' => $transactionId,
                        'dotpayTransactionStatus' => $transactionStatus,
                    ]
                );

                return new Response('FAIL, TRANSACTION IS COMPLETED', 500);
            }

            $logger->error(
                '[Dotpay - URLC - {dotpayTransactionId}] no pending transaction found for the payment instruction',
                [
                    'paymentInstructionId' => $instruction->getId(),
                    'dotpayTransactionId' => $transactionId,
                    'dotpayTransactionStatus' => $transactionStatus,
                ]
            );
        }

        $amountParts = explode(' ', $request->get('orginal_amount')); // Yes, the right parameter is 'orginal_amount'
        $amount = (float) $amountParts[0]; // there is a typo error in the DotPay API

        $transaction->getExtendedData()->set('t_status', $transactionStatus);
        $transaction->getExtendedData()->set('t_id', $transactionId);
        $transaction->getExtendedData()->set('amount', $amount);

        try {
            $ppc->approveAndDeposit($transaction->getPayment()->getId(), $amount);
        } catch (\Exception $exception) {
            $logger->error(
                '[Dotpay - URLC - {dotpayTransactionId}] error {exceptionClass} {exceptionMessage}',
                [
                    'paymentInstructionId' => $instruction->getId(),
                    'dotpayTransactionId' => $transactionId,
                    'dotpayTransactionStatus' => $transactionStatus,
                    'exceptionClass' => get_class($exception),
                    'exceptionMessage' => $exception->getMessage(),
                ]
            );

            return new Response('FAIL APPROVE AND DEPOSIT', 500);
        }

        $this->getDoctrine()->getManager()->flush();

        $logger->info(
            '[Dotpay - URLC - {dotpayTransactionId}] Payment instruction {paymentInstructionId} successfully updated',
            [
                'paymentInstructionId' => $instruction->getId(),
                'dotpayTransactionId' => $transactionId,
                'dotpayTransactionStatus' => $transactionStatus,
            ]
        );

        return new Response('OK');
    }
}
