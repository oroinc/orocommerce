<?php

namespace OroB2B\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

abstract class AbstractCallbackEvent extends Event
{
    /** @var string */
    protected $queryString;

    /** @var Response */
    protected $response;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /**
     * @param string $queryString
     */
    public function __construct($queryString)
    {
        $this->queryString = $queryString;

        $this->response = new Response();
    }

    /**
     * @return string
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * @param string $type
     * @return string
     */
    public function getTypedEventName($type)
    {
        return implode('.', [$this->getEventName(), strtolower($type)]);
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    abstract public function getEventName();

    /**
     * @return PaymentTransaction
     */
    public function getPaymentTransaction()
    {
        return $this->paymentTransaction;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function setPaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $this->paymentTransaction = $paymentTransaction;
    }
}
