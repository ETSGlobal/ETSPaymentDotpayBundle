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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * Dotpay payment plugin
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class DotpayDirectPluginTest extends TestCase
{
    /** @var DotpayDirectPlugin */
    private $dotpayDirectPlugin;

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

    /**
     * @param array $data
     * @param $pin
     * @param $expected
     *
     * @dataProvider provideDataForGenerateChk
     */
    public function testGenerateChk(array $data, $pin, $expected)
    {
        $chk = $this->invokeMethod($this->dotpayDirectPlugin, 'generateChk', [$data, $pin]);

        $this->assertEquals($expected, $chk);
    }

    public function provideDataForGenerateChk()
    {
        return [
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.11111,
                    'currency' => 'EUR',
                    'description' => 'my_fake_description',
                    'control' => 'my_fake_control',
                    'channel' => 'my_fake_chanel',
                    'chlock' => 'my_fake_chlock',
                    'data_waznosci' => 'my_fake_data_waznosci',
                    'data_zapadalnosci' => 'my_fake_data_zapadalnosci',
                    'recipientChk' => 'my_fake_recipientChk',
                ],
                'my_fake_pin',
                md5('my_fake_id11.11EURmy_fake_descriptionmy_fake_controlmy_fake_pinmy_fake_chanelmy_fake_chlockmy_fake_data_zapadalnoscimy_fake_data_waznoscimy_fake_recipientChk'),
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.11111,
                    'currency' => 'EUR',
                    'description' => 'my_fake_description',
                    'control' => 'my_fake_control',
                    'channel' => 'my_fake_chanel',
                    'chlock' => 'my_fake_chlock',
                    'data_waznosci' => 'my_fake_data_waznosci',
                    'data_zapadalnosci' => 'my_fake_data_zapadalnosci',
                ],
                'my_fake_pin',
                md5('my_fake_id11.11EURmy_fake_descriptionmy_fake_controlmy_fake_pinmy_fake_chanelmy_fake_chlockmy_fake_data_zapadalnoscimy_fake_data_waznosci'),
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.11111,
                    'currency' => 'EUR',
                    'description' => 'my_fake_description',
                    'control' => 'my_fake_control',
                    'channel' => 'my_fake_chanel',
                    'chlock' => 'my_fake_chlock',
                    'data_zapadalnosci' => 'my_fake_data_zapadalnosci',
                    'recipientChk' => 'my_fake_recipientChk',
                ],
                'my_fake_pin',
                md5('my_fake_id11.11EURmy_fake_descriptionmy_fake_controlmy_fake_pinmy_fake_chanelmy_fake_chlockmy_fake_recipientChk'),
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.11111,
                    'currency' => 'EUR',
                    'description' => 'my_fake_description',
                    'control' => 'my_fake_control',
                    'channel' => 'my_fake_chanel',
                    'chlock' => 'my_fake_chlock',
                    'data_waznosci' => 'my_fake_data_waznosci',
                    'recipientChk' => 'my_fake_recipientChk',
                ],
                'my_fake_pin',
                md5('my_fake_id11.11EURmy_fake_descriptionmy_fake_controlmy_fake_pinmy_fake_chanelmy_fake_chlockmy_fake_data_waznoscimy_fake_recipientChk'),
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.11111,
                    'currency' => 'EUR',
                    'description' => 'my_fake_description',
                    'control' => 'my_fake_control',
                    'chlock' => 'my_fake_chlock',
                    'data_waznosci' => 'my_fake_data_waznosci',
                    'data_zapadalnosci' => 'my_fake_data_zapadalnosci',
                    'recipientChk' => 'my_fake_recipientChk',
                ],
                'my_fake_pin',
                md5('my_fake_id11.11EURmy_fake_descriptionmy_fake_controlmy_fake_pinmy_fake_data_zapadalnoscimy_fake_data_waznoscimy_fake_recipientChk'),
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.11111,
                    'currency' => 'EUR',
                    'description' => 'my_fake_description',
                    'control' => 'my_fake_control',
                    'channel' => 'my_fake_chanel',
                    'data_waznosci' => 'my_fake_data_waznosci',
                    'data_zapadalnosci' => 'my_fake_data_zapadalnosci',
                    'recipientChk' => 'my_fake_recipientChk',
                ],
                'my_fake_pin',
                md5('my_fake_id11.11EURmy_fake_descriptionmy_fake_controlmy_fake_pinmy_fake_chanelmy_fake_data_zapadalnoscimy_fake_data_waznoscimy_fake_recipientChk'),
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.11111,
                    'currency' => 'EUR',
                    'description' => 'my_fake_description',
                    'channel' => 'my_fake_chanel',
                    'chlock' => 'my_fake_chlock',
                    'data_waznosci' => 'my_fake_data_waznosci',
                    'data_zapadalnosci' => 'my_fake_data_zapadalnosci',
                    'recipientChk' => 'my_fake_recipientChk',
                ],
                'my_fake_pin',
                md5('my_fake_id11.11EURmy_fake_descriptionmy_fake_pinmy_fake_chanelmy_fake_chlockmy_fake_data_zapadalnoscimy_fake_data_waznoscimy_fake_recipientChk'),
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.11111,
                    'currency' => 'EUR',
                    'description' => 'my_fake_description',
                ],
                'my_fake_pin',
                md5('my_fake_id11.11EURmy_fake_descriptionmy_fake_pin'),
            ],
        ];
    }

    /**
     * @param array $data
     * @param $pin
     * @param $expected
     *
     * @dataProvider provideDataForGenerateRecipientChk
     */
    public function testGenerateRecipientChk(array $data, $pin, $expected)
    {
        $recipientChk = $this->invokeMethod($this->dotpayDirectPlugin, 'generateRecipientChk', [$data, $pin]);

        $this->assertEquals($expected, $recipientChk);
    }

    public function provideDataForGenerateRecipientChk()
    {
        return [
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                    'recipientAccountNumber' => 'my_fake_recipientAccountNumber',
                    'recipientCompany' => 'my_fake_recipientCompany',
                    'recipientFirstName' => 'my_fake_recipientFirstName',
                    'recipientLastName' => 'my_fake_recipientLastName',
                    'recipientAddressStreet' => 'my_fake_recipientAddressStreet',
                    'recipientAddressBuilding' => 'my_fake_recipientAddressBuilding',
                    'recipientAddressApartment' => 'my_fake_recipientAddressApartment',
                    'recipientAddressPostcode' => 'my_fake_recipientAddressPostcode',
                    'recipientAddressCity' => 'my_fake_recipientAddressCity',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_recipientAccountNumbermy_fake_recipientCompanymy_fake_recipientFirstNamemy_fake_recipientLastNamemy_fake_recipientAddressStreetmy_fake_recipientAddressBuildingmy_fake_recipientAddressApartmentmy_fake_recipientAddressPostcodemy_fake_recipientAddressCitymy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                    'recipientAccountNumber' => 'my_fake_recipientAccountNumber',
                    'recipientCompany' => 'my_fake_recipientCompany',
                    'recipientFirstName' => 'my_fake_recipientFirstName',
                    'recipientLastName' => 'my_fake_recipientLastName',
                    'recipientAddressStreet' => 'my_fake_recipientAddressStreet',
                    'recipientAddressBuilding' => 'my_fake_recipientAddressBuilding',
                    'recipientAddressApartment' => 'my_fake_recipientAddressApartment',
                    'recipientAddressPostcode' => 'my_fake_recipientAddressPostcode',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_recipientAccountNumbermy_fake_recipientCompanymy_fake_recipientFirstNamemy_fake_recipientLastNamemy_fake_recipientAddressStreetmy_fake_recipientAddressBuildingmy_fake_recipientAddressApartmentmy_fake_recipientAddressPostcodemy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                    'recipientAccountNumber' => 'my_fake_recipientAccountNumber',
                    'recipientCompany' => 'my_fake_recipientCompany',
                    'recipientFirstName' => 'my_fake_recipientFirstName',
                    'recipientLastName' => 'my_fake_recipientLastName',
                    'recipientAddressStreet' => 'my_fake_recipientAddressStreet',
                    'recipientAddressBuilding' => 'my_fake_recipientAddressBuilding',
                    'recipientAddressApartment' => 'my_fake_recipientAddressApartment',
                    'recipientAddressCity' => 'my_fake_recipientAddressCity',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_recipientAccountNumbermy_fake_recipientCompanymy_fake_recipientFirstNamemy_fake_recipientLastNamemy_fake_recipientAddressStreetmy_fake_recipientAddressBuildingmy_fake_recipientAddressApartmentmy_fake_recipientAddressCitymy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                    'recipientAccountNumber' => 'my_fake_recipientAccountNumber',
                    'recipientCompany' => 'my_fake_recipientCompany',
                    'recipientFirstName' => 'my_fake_recipientFirstName',
                    'recipientLastName' => 'my_fake_recipientLastName',
                    'recipientAddressStreet' => 'my_fake_recipientAddressStreet',
                    'recipientAddressBuilding' => 'my_fake_recipientAddressBuilding',
                    'recipientAddressPostcode' => 'my_fake_recipientAddressPostcode',
                    'recipientAddressCity' => 'my_fake_recipientAddressCity',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_recipientAccountNumbermy_fake_recipientCompanymy_fake_recipientFirstNamemy_fake_recipientLastNamemy_fake_recipientAddressStreetmy_fake_recipientAddressBuildingmy_fake_recipientAddressPostcodemy_fake_recipientAddressCitymy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                    'recipientAccountNumber' => 'my_fake_recipientAccountNumber',
                    'recipientCompany' => 'my_fake_recipientCompany',
                    'recipientFirstName' => 'my_fake_recipientFirstName',
                    'recipientLastName' => 'my_fake_recipientLastName',
                    'recipientAddressStreet' => 'my_fake_recipientAddressStreet',
                    'recipientAddressApartment' => 'my_fake_recipientAddressApartment',
                    'recipientAddressPostcode' => 'my_fake_recipientAddressPostcode',
                    'recipientAddressCity' => 'my_fake_recipientAddressCity',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_recipientAccountNumbermy_fake_recipientCompanymy_fake_recipientFirstNamemy_fake_recipientLastNamemy_fake_recipientAddressStreetmy_fake_recipientAddressApartmentmy_fake_recipientAddressPostcodemy_fake_recipientAddressCitymy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                    'recipientAccountNumber' => 'my_fake_recipientAccountNumber',
                    'recipientCompany' => 'my_fake_recipientCompany',
                    'recipientFirstName' => 'my_fake_recipientFirstName',
                    'recipientLastName' => 'my_fake_recipientLastName',
                    'recipientAddressBuilding' => 'my_fake_recipientAddressBuilding',
                    'recipientAddressApartment' => 'my_fake_recipientAddressApartment',
                    'recipientAddressPostcode' => 'my_fake_recipientAddressPostcode',
                    'recipientAddressCity' => 'my_fake_recipientAddressCity',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_recipientAccountNumbermy_fake_recipientCompanymy_fake_recipientFirstNamemy_fake_recipientLastNamemy_fake_recipientAddressBuildingmy_fake_recipientAddressApartmentmy_fake_recipientAddressPostcodemy_fake_recipientAddressCitymy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                    'recipientAccountNumber' => 'my_fake_recipientAccountNumber',
                    'recipientCompany' => 'my_fake_recipientCompany',
                    'recipientFirstName' => 'my_fake_recipientFirstName',
                    'recipientAddressStreet' => 'my_fake_recipientAddressStreet',
                    'recipientAddressBuilding' => 'my_fake_recipientAddressBuilding',
                    'recipientAddressApartment' => 'my_fake_recipientAddressApartment',
                    'recipientAddressPostcode' => 'my_fake_recipientAddressPostcode',
                    'recipientAddressCity' => 'my_fake_recipientAddressCity',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_recipientAccountNumbermy_fake_recipientCompanymy_fake_recipientFirstNamemy_fake_recipientAddressStreetmy_fake_recipientAddressBuildingmy_fake_recipientAddressApartmentmy_fake_recipientAddressPostcodemy_fake_recipientAddressCitymy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                    'recipientAccountNumber' => 'my_fake_recipientAccountNumber',
                    'recipientCompany' => 'my_fake_recipientCompany',
                    'recipientLastName' => 'my_fake_recipientLastName',
                    'recipientAddressStreet' => 'my_fake_recipientAddressStreet',
                    'recipientAddressBuilding' => 'my_fake_recipientAddressBuilding',
                    'recipientAddressApartment' => 'my_fake_recipientAddressApartment',
                    'recipientAddressPostcode' => 'my_fake_recipientAddressPostcode',
                    'recipientAddressCity' => 'my_fake_recipientAddressCity',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_recipientAccountNumbermy_fake_recipientCompanymy_fake_recipientLastNamemy_fake_recipientAddressStreetmy_fake_recipientAddressBuildingmy_fake_recipientAddressApartmentmy_fake_recipientAddressPostcodemy_fake_recipientAddressCitymy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                    'recipientAccountNumber' => 'my_fake_recipientAccountNumber',
                    'recipientFirstName' => 'my_fake_recipientFirstName',
                    'recipientLastName' => 'my_fake_recipientLastName',
                    'recipientAddressStreet' => 'my_fake_recipientAddressStreet',
                    'recipientAddressBuilding' => 'my_fake_recipientAddressBuilding',
                    'recipientAddressApartment' => 'my_fake_recipientAddressApartment',
                    'recipientAddressPostcode' => 'my_fake_recipientAddressPostcode',
                    'recipientAddressCity' => 'my_fake_recipientAddressCity',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_recipientAccountNumbermy_fake_recipientFirstNamemy_fake_recipientLastNamemy_fake_recipientAddressStreetmy_fake_recipientAddressBuildingmy_fake_recipientAddressApartmentmy_fake_recipientAddressPostcodemy_fake_recipientAddressCitymy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                    'recipientCompany' => 'my_fake_recipientCompany',
                    'recipientFirstName' => 'my_fake_recipientFirstName',
                    'recipientLastName' => 'my_fake_recipientLastName',
                    'recipientAddressStreet' => 'my_fake_recipientAddressStreet',
                    'recipientAddressBuilding' => 'my_fake_recipientAddressBuilding',
                    'recipientAddressApartment' => 'my_fake_recipientAddressApartment',
                    'recipientAddressPostcode' => 'my_fake_recipientAddressPostcode',
                    'recipientAddressCity' => 'my_fake_recipientAddressCity',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_recipientCompanymy_fake_recipientFirstNamemy_fake_recipientLastNamemy_fake_recipientAddressStreetmy_fake_recipientAddressBuildingmy_fake_recipientAddressApartmentmy_fake_recipientAddressPostcodemy_fake_recipientAddressCitymy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'control' => 'my_fake_control',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_controlmy_fake_pin')
            ],
            [
                [
                    'id' => 'my_fake_id',
                    'amount' => 11.1111,
                    'currency' => 'EUR',
                    'recipientAccountNumber' => 'my_fake_recipientAccountNumber',
                    'recipientCompany' => 'my_fake_recipientCompany',
                    'recipientFirstName' => 'my_fake_recipientFirstName',
                    'recipientLastName' => 'my_fake_recipientLastName',
                    'recipientAddressStreet' => 'my_fake_recipientAddressStreet',
                    'recipientAddressBuilding' => 'my_fake_recipientAddressBuilding',
                    'recipientAddressApartment' => 'my_fake_recipientAddressApartment',
                    'recipientAddressPostcode' => 'my_fake_recipientAddressPostcode',
                    'recipientAddressCity' => 'my_fake_recipientAddressCity',
                ],
                'my_fake_pin',
                hash('sha256', 'my_fake_id11.11EURmy_fake_recipientAccountNumbermy_fake_recipientCompanymy_fake_recipientFirstNamemy_fake_recipientLastNamemy_fake_recipientAddressStreetmy_fake_recipientAddressBuildingmy_fake_recipientAddressApartmentmy_fake_recipientAddressPostcodemy_fake_recipientAddressCitymy_fake_pin')
            ],
        ];
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    private function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
