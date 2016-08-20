<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Model;

interface SubtotalProviderInterface
{
    /**
     * Get provider name
     *
     * @return string
     */
    public function getName();

    /**
     * Get entity subtotal
     *
     * @param $entity
     *
     * @return Subtotal[]|Subtotal
     */
    public function getSubtotal($entity);

    /**
     * Check to support provider entity
     *
     * @param $entity
     *
     * @return boolean
     */
    public function isSupported($entity);
}
