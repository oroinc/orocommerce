<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Response;

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
    public function getResult();


    /**
     * @return string
     */
    public function getErrorMessage();


    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return array
     */
    public function getData();
}
