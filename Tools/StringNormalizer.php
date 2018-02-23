<?php

namespace ETS\Payment\DotpayBundle\Tools;

/**
 * StringNormalizer tools
 */
class StringNormalizer
{
    /**
     * Remove all unwanted caracters
     *
     * @param string $text
     *
     * @return string
     */
    public function normalize($text)
    {
        return preg_replace('/\pM*/u', '', \Normalizer::normalize($text, \Normalizer::FORM_D));
    }
}
