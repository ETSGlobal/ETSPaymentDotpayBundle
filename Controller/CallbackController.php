<?php

namespace ETS\Payment\DotpayBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
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
            $logger->err('[Dotpay - URLC] pin verification failed');

            return new Response('FAIL', 500);
        }

        if (null === $transaction = $instruction->getPendingTransaction()) {
            $logger->err('[Dotpay - URLC] no pending transaction found for the payment instruction');

            return new Response('FAIL', 500);
        }

        $amountParts = explode(' ', $request->get('orginal_amount')); // Yes, the right parameter is 'orginal_amount'
        $amount = (float) $amountParts[0];                            // there is a typo error in the DotPay API

        $transaction->getExtendedData()->set('t_status', $request->get('t_status'));
        $transaction->getExtendedData()->set('t_id', $request->get('t_id'));
        $transaction->getExtendedData()->set('amount', $amount);

        try {
            $this->get('payment.plugin_controller')->approveAndDeposit($transaction->getPayment()->getId(), $amount);
        } catch (\Exception $e) {
            $logger->err(sprintf('[Dotpay - URLC] %s', $e->getMessage()));

            return new Response('FAIL', 500);
        }

        $this->getDoctrine()->getEntityManager()->flush();

        $logger->info(sprintf('[Dotpay - URLC] Payment instruction %s successfully updated', $instruction->getId()));

        return new Response('OK');
    }
}
