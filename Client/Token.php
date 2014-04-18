<?php

namespace ETS\Payment\DotpayBundle\Client;

use ETS\Payment\DotpayBundle\Client\TokenInterface;

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
 * Token
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class Token implements TokenInterface
{
    protected $id;
    protected $pin;

    /**
     * @param string $id  The Seller ID
     * @param string $pin The URLC PIN
     */
    public function __construct($id, $pin)
    {
        $this->id = $id;
        $this->pin = $pin;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPin()
    {
        return $this->pin;
    }
}
