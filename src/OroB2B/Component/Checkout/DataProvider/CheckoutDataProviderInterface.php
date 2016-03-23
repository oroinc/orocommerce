<?php

namespace OroB2B\Component\Checkout\DataProvider;

interface CheckoutDataProviderInterface
{
    /**
     * @param object|array $transformData
     * @return array
     */
    public function getData($transformData);

    /**
     * @param object|array $transformData
     * @return boolean
     */
    public function isTransformDataSupported($transformData);
}
