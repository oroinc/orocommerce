<?php

namespace OroB2B\Component\Checkout\DataProvider;

interface CheckoutDataProviderInterface
{
    /**
     * @param object|array $entity
     * @param array $additionalData
     * @return array
     */
    public function getData($entity, $additionalData);

    /**
     * @param object|array $transformData
     * @return boolean
     */
    public function isEntitySupported($transformData);
}
