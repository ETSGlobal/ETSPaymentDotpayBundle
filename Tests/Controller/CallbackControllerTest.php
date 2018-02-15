<?php

namespace ETS\Payment\DotpayBundle\Tests\Controller;

use ETS\Payment\DotpayBundle\Test\ContainerAwareWebTestCase;
use ETS\Payment\DotpayBundle\Plugin\DotpayDirectPlugin;

use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;

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
 * Callback controller test
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class CallbackControllerTest extends ContainerAwareWebTestCase
{
    /**
     * Test the the creation of a new payment
     *
     * @return PaymentInstructionInterface
     */
    public function testNewPaymentInstruction()
    {
        $form = $this->get('form.factory')->create('jms_choose_payment_method', null, array(
            'amount'   => 42,
            'currency' => 'EUR',
            'default_method' => 'dotpay_direct',
            'predefined_data' => array(
                'dotpay_direct' => array(
                    'lang'       => 'en',
                    'return_url' => 'http://test.com',
                ),
            ),
            'csrf_protection' => false,
        ));

        $form->submit(array(
            'method' => 'dotpay_direct',
        ));

        if (!$form->isValid()) {
            $this->fail("The form should be valid at this point");
        }

        $ppc = $this->get('payment.plugin_controller');
        $ppc->createPaymentInstruction($instruction = $form->getData());

        $this->assertNull($instruction->getPendingTransaction());

        $payment = $ppc->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());

        $this->assertNotNull($payment);

        $result = $ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());

        $this->assertEquals(Result::STATUS_PENDING, $result->getStatus());

        $ex = $result->getPluginException();

        $this->assertTrue($ex instanceof ActionRequiredException);

        $action = $ex->getAction();

        $this->assertTrue($action instanceof VisitUrl);

        $this->assertContains('dotpay', $action->getUrl());

        return $instruction;
    }

    /**
     * test the URLC callback action
     *
     * @param PaymentInstructionInterface $paymentInstruction
     *
     * @depends testNewPaymentInstruction
     */
    public function testUrlcActionWithBadPin(PaymentInstructionInterface $paymentInstruction)
    {
        $route = $this->get('router')->generate('ets_payment_dotpay_callback_urlc', array(
            'id' => $paymentInstruction->getId(),
        ), true);

        static::$client->request('POST', $route, array(
            'id' => 424242,
            't_id' => '424242-TST1',
            'control' => '',
            'amount' => 42.00,
            'email' => 'clement.gautier.76@gmail.com',
            'description' => 'Test transaction',
            't_status' => DotpayDirectPlugin::STATUS_NEW,
            'code' => '',
            'service' => '',
            'md5' => '42',
        ));

        $this->assertFalse(static::$client->getResponse()->isSuccessful());
        $this->assertEquals('FAIL', static::$client->getResponse()->getContent());
    }

    /**
     * test the URLC callback action
     *
     * @param PaymentInstructionInterface $paymentInstruction
     *
     * @depends testNewPaymentInstruction
     */
    public function testUrlcAction(PaymentInstructionInterface $paymentInstruction)
    {
        $route = $this->get('router')->generate('ets_payment_dotpay_callback_urlc', array(
            'id' => $paymentInstruction->getId(),
        ), true);

        static::$client->request('POST', $route, array(
            'id' => 424242,
            't_id' => '424242-TST1',
            'control' => '',
            'orginal_amount' => '42.00 EUR',
            'amount' => 42.00,
            'email' => 'clement.gautier.76@gmail.com',
            'description' => 'Test transaction',
            't_status' => DotpayDirectPlugin::STATUS_NEW,
            'code' => '',
            'service' => '',
            'md5' => md5(sprintf(
                "%s:%s:%s:%s:%s:%s:%s:%s:%s:%s:%s",
                $this->getContainer()->getParameter('payment.dotpay.direct.pin'),
                $this->getContainer()->getParameter('payment.dotpay.direct.id'),
                '', '424242-TST1', 42.00, 'clement.gautier.76@gmail.com',
                '', '', '', '', DotpayDirectPlugin::STATUS_NEW
            ))
        ));

        $this->assertTrue(static::$client->getResponse()->isSuccessful(), static::$client->getResponse()->getContent());
        $this->assertEquals('OK', static::$client->getResponse()->getContent());
    }
}
