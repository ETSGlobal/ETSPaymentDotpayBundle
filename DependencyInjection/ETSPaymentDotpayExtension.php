<?php

namespace ETS\Payment\DotpayBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
 * Bundle Extension class
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class ETSPaymentDotpayExtension extends Extension
{
    /**
     * @param array            $configs   The configuration
     * @param ContainerBuilder $container The container builder
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        $xmlLoader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $xmlLoader->load('services.xml');

        $container->setParameter('payment.dotpay.direct.id', $config['direct']['id']);
        $container->setParameter('payment.dotpay.direct.pin', $config['direct']['pin']);
        $container->setParameter('payment.dotpay.direct.url', $config['direct']['url']);
        $container->setParameter('payment.dotpay.direct.type', $config['direct']['type']);
        $container->setParameter('payment.dotpay.direct.return_url', $config['direct']['return_url']);
    }
}
