<?php

namespace ETS\Payment\DotpayBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\ORM\Tools\SchemaTool;

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

    protected $em;
    protected $tool;

    /**
     * @return null
     */
    public function setUp()
    {
        self::$client = self::createClient();

        parent::setUp();

        $this->setUpDoctrine();
    }

    /**
     * @return null
     */
    public function tearDown()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }

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

    /**
     * @inheritDoc
     */
    protected static function getKernelClass()
    {
        require_once __DIR__.'/AppKernel.php';

        return 'ETS\Payment\DotpayBundle\Test\AppKernel';
    }

    protected function setUpDoctrine()
    {
        $this->em = $this->get('doctrine.orm.default_entity_manager');
        PersistentObject::setObjectManager($this->em);

        $this->tool = new SchemaTool($this->em);
        $this->tool->updateSchema($this->em->getMetadataFactory()->getAllMetadata(), true);
    }

    protected function dropDoctrine()
    {
        $this->tool->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
    }
}
