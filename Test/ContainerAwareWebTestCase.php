<?php

namespace ETS\Payment\DotpayBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * ContainerAwareUnitTestCase
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