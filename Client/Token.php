<?php

namespace ETS\Payment\DotpayBundle\Client;

use ETS\Payment\DotpayBundle\Client\TokenInterface;

/**
 * Token
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
