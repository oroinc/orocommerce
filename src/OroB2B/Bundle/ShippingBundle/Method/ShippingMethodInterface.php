<?php

namespace OroB2B\Bundle\ShippingBundle\Method;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;

interface ShippingMethodInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return array
     */
    public function getTypes();

    /**
     * @return string
     */
    public function getFormType();

    /**
     * @param ShippingRuleConfiguration $entity
     * @return float
     */
    public function calculatePrice(ShippingRuleConfiguration $entity);
}
