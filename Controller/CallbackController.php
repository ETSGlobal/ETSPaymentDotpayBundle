<?php

namespace ETS\Payment\DotpayBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use ETS\Payment\DotpayBundle\Event\DotpayConfirmationReceivedEvent;
use ETS\Payment\DotpayBundle\Event\Events as DotpayEvents;
use JMS\Payment\CoreBundle\PluginController\Result;

/*
 * Copyright 2012 ETSGlobal <e4-devteam@etsglobal.org>
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
 * @author ETSGlobal <e4-devteam@etsglobal.org>
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

        $t_id = $request->request->get('t_id');
        $amount = (float) $request->get('amount');

        // Check the PIN
        $control = md5(sprintf(
            "%s:%s:%s:%s:%s:%s:%s:%s:%s:%s:%s",
            $client->getPin(),
            $client->getId(),
            $request->request->get('control'),
            $request->request->get('t_id'),
            $request->request->get('amount'),
            $request->request->get('email'),
            $request->request->get('service'),
            $request->request->get('code'),
            $request->request->get('username'),
            $request->request->get('password'),
            $request->request->get('t_status')
        ));

        if ($control !== $request->request->get('md5')) {
            $logger->err('[Dotpay - URLC - ' . $t_id .'] pin verification failed');

            return new Response('FAIL', 500);
        }

        // Handling payment:
        if (null === $transaction = $instruction->getPendingTransaction()) {

            if ($instruction->getAmount() > $instruction->getDepositedAmount()) {

                $logger->err('[Dotpay - URLC - ' . $t_id .'] no pending transaction found for the payment instruction, creating new one');
                $payment = $ppc->createPayment($instruction->getId(), $amount);
                $ppc->approveAndDeposit($payment->getId(), $amount);
                $transaction = $payment->getPendingTransaction();

                if (null === $transaction) {
                    $logger->err('[Dotpay - URLC - ' . $t_id .'] error while creating new transaction');

                    return new Response('FAIL', 500);
                }

            } else {
                $logger->err('[Dotpay - URLC - ' . $t_id .'] unable to create new transaction, all of amount has been deposited');

                return new Response('FAIL', 500);
            }
        }

        $transaction->getExtendedData()->set('t_status', $request->get('t_status'));
        $transaction->getExtendedData()->set('t_id', $request->get('t_id'));
        $transaction->getExtendedData()->set('amount', $amount);

        try {
            $ppc->approveAndDeposit($transaction->getPayment()->getId(), $amount);
        } catch (\Exception $e) {
            $logger->err(sprintf('[Dotpay - URLC - ' . $t_id .'] %s', $e->getMessage()));

            return new Response('FAIL', 500);
        }

        $this->getDoctrine()->getManager()->flush();

        $logger->info(sprintf('[Dotpay - URLC - ' . $t_id .'] Payment instruction %s successfully updated', $instruction->getId()));

        return new Response('OK');
    }
}
