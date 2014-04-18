<?php

namespace ETS\PurchaseBundle\Tests\Tools;

use ETS\Payment\DotpayBundle\Tools\String;

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
 * String Tests
 *
 * @author ETSGlobal <ecs@etsglobal.org>
 */
class StringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function normalizeProvider()
    {
        return array(
            array("Clément", "Clement"),
            array("eéèêëiîïoöôuùûüaâäÅ Ἥ ŐǟǠ ǺƶƈƉųŪŧȬƀ␢ĦŁȽŦ ƀǖ", "eeeeeiiiooouuuuaaaA Η OaA AƶƈƉuUŧOƀ␢ĦŁȽŦ ƀu"),
            array("Fóø Bår", "Foø Bar"),
        );
    }

    /**
     * @param string $input    Input string
     * @param string $expected Expected output string
     *
     * @dataProvider normalizeProvider
     */
    public function testNormaize($input, $expected)
    {
        $tool = new String();

        $this->assertEquals($expected, $tool->normalize($input));
    }
}
