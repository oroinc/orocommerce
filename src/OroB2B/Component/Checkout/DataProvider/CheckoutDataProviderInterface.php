<?php

namespace OroB2B\Component\Checkout\DataProvider;

interface CheckoutDataProviderInterface
{
    /**
     * @param object $entity
     * @return array
     */
    public function getData($entity);

    /**
     * @param object $entity
     * @return boolean
     */
    public function isEntitySupported($entity);
}
