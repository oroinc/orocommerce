<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response;

interface ResponseInterface
{
    /**
     * @return bool
     */
    public function isSuccessful();

    /**
     * @return string
     */
    public function getReference();

    /**
     * @return string
     */
    public function getState();

    /**
     * @return array
     */
    public function getData();
}
