<?php

namespace Oro\Component\Checkout\DataProvider;

interface CheckoutDataProviderInterface
{
    /**
     * @param object|array $entity
     * @return array
     */
    public function getData($entity);

    /**
     * @param object|array $transformData
     * @return boolean
     */
    public function isEntitySupported($transformData);
}
