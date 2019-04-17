<?php

namespace ETS\Payment\DotpayBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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
 * Bundle Configuration
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        return $treeBuilder
            ->root('ets_payment_dotpay')
                ->children()
                    ->arrayNode('direct')
                        ->children()
                            ->scalarNode('id')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('pin')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('url')
                                ->defaultValue('https://ssl.dotpay.pl/')
                                ->cannotBeEmpty()
                                ->validate()
                                    ->ifNotInArray(['https://ssl.dotpay.pl/', 'https://ssl.dotpay.eu/'])
                                    ->thenInvalid('Invalid dotpay url "%s"')
                                ->end()
                            ->end()
                            ->scalarNode('type')
                                ->defaultValue(2)
                                ->validate()
                                    ->ifNotInArray([0, 1, 2, 3])
                                    ->thenInvalid('Invalid type "%s"')
                                ->end()
                            ->end()
                            ->scalarNode('return_url')->defaultNull()->end()
                            ->booleanNode('chk')
                                ->defaultFalse()
                            ->end()
                            ->booleanNode('recipientChk')
                                ->defaultFalse()
                            ->end()
                            ->booleanNode('onlineTransfer')
                                ->defaultFalse()
                            ->end()
                            ->integerNode('expirationTime')
                                ->defaultValue(0)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
