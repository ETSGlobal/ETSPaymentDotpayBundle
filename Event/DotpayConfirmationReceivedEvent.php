<?php

namespace ETS\Payment\DotpayBundle\Event;

use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\ParameterBag;

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
 * DotpayConfirmationReceivedEvent
 * This event is dispatched when the urlc action is called by dotpay.
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class DotpayConfirmationReceivedEvent extends Event
{
    private $instruction;
    private $parameters;

    /**
     * @param \JMS\Payment\CoreBundle\Model\PaymentInstructionInterface $instruction
     * @param \Symfony\Component\HttpFoundation\ParameterBag $parameters
     */
    public function __construct(PaymentInstructionInterface $instruction, ParameterBag $parameters)
    {
        $this->instruction = $instruction;
        $this->parameters = $parameters;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    public function getRequestParameters()
    {
        return $this->parameters;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Model\PaymentInstructionInterface
     */
    public function getPaymentInstruction()
    {
        return $this->instruction;
    }
}
