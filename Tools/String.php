<?php

namespace ETS\Payment\DotpayBundle\Tools;

/**
 * String tools
 */
class String
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
