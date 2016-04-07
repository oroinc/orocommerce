<?php

namespace OroB2B\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

abstract class AbstractCallbackEvent extends Event
{
    /** @var string */
    protected $data;

    /** @var Response */
    protected $response;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;

        $this->response = new Response();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $type
     * @return string
     */
    public function getTypedEventName($type)
    {
        return implode('.', [$this->getEventName(), $type]);
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
