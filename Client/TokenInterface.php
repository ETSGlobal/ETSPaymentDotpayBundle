<?php

namespace ETS\Payment\DotpayBundle\Client;

/**
 * Token interface
 */
interface TokenInterface
{
    /**
     * Return the Seller ID
     *
     * @return string
     */
    function getId();

    /**
     * Return the URLC PIN code
     *
     * @return string
     */
    function getPin();
}