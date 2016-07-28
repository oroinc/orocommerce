<?php

namespace OroB2B\Bundle\ShippingBundle\Method;

use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Form\Type\FlatRateShippingConfigurationType;

class FlatRateShippingMethod implements ShippingMethodInterface
{
    const NAME = 'flat_rate';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes()
    {
        /** Here we have only one type because we need only one form for both types */
        return [
            FlatRateRuleConfiguration::TYPE_PER_ORDER,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return FlatRateShippingConfigurationType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrice(ShippingRuleConfiguration $entity)
    {
        // TODO: will be implemented in BB-2815
    }
}
