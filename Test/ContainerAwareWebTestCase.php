<?php

namespace ETS\Payment\DotpayBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
 * ContainerAwareUnitTestCase
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class ContainerAwareWebTestCase extends WebTestCase
{
    /**
     * @var Symfony\Component\HttpKernel\AppKernel
     */
    protected static $client;

    /**
     * @return null
     */
    public function setUp()
    {
        self::$client = self::createClient();

        parent::setUp();
    }

    /**
     * @return null
     */
    public function tearDown()
    {
        static::$kernel->shutdown();

        parent::tearDown();
    }

    /**
     * @param string $service
     *
     * @return mixed
     */
    public function get($service)
    {
        return static::$kernel->getContainer()->get($service);
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        return static::$kernel->getContainer();
    }
}
