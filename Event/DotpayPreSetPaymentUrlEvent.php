<?php

namespace ETS\Payment\DotpayBundle\Event;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use Symfony\Component\EventDispatcher\Event;

class DotpayPreSetPaymentUrlEvent extends Event
{
    protected $transaction;
    protected $baseUrl;
    protected $queryParameters;
    
    /**
     * @param FinancialTransactionInterface $transaction
     * @param string                        $baseUrl
     * @param array                         $queryParameters
     */
    public function __construct(FinancialTransactionInterface $transaction, $baseUrl, array $queryParameters = array())
    {
        $this->transaction = $transaction;
        
        $this->setBaseUrl($baseUrl);
        $this->setQueryParameters($queryParameters);
    }
    
    /**
     * @return FinancialTransactionInterface
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
    
    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
    
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * @return array
     */
    public function getQueryParameters()
    {
        return $this->queryParameters;
    }
    
    public function setQueryParameters(array $queryParameters)
    {
        $this->queryParameters = $queryParameters;
    }
}