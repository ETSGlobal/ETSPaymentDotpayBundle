<?php

namespace ETS\Payment\DotpayBundle\Event;

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
 * Events triggered by the Dotpay bundle
 *
 * @author ETSGlobal <e4-devteam@etsglobal.org>
 */
abstract class Events
{
    /**
     * This event is dispatched when the urlc action is called by dotpay.
     */
    const PAYMENT_DOTPAY_CONFIRMATION_RECEIVED = 'payment.dotpay.confirmation_received';
    
    const PAYMENT_DOTPAY_PRE_SET_PAYMENT_URL = 'payment.dotpay.pre_set_payment_url';

    private final function __construct() { }
}