<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

interface PaymentMethodInterface
{
    const AUTHORIZE = 'authorize';
    const CAPTURE = 'capture';
    const CHARGE = 'charge';
    const VOID = 'void';

    /**
     * @param string $actionName
     * @param array $options
     * @return array
     */
    public function action($actionName, array $options = []);

    /**
     * @return string
     */
    public function getType();
}
