<?php

namespace OroB2B\Bundle\ShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;

class FlatRate implements ShippingMethodInterface
{
    const NAME = 'flat_rate';

    public function getName()
    {
        return self::NAME;
    }

    public function getLabel()
    {
        // TODO: Implement getLabel() method.
    }

    public function getShippingTypes()
    {
        // TODO: Implement getShippingTypes() method.
    }

    public function getShippingTypeLabel($type)
    {
        // TODO: Implement getShippingTypeLabel() method.
    }

    public function getFormType()
    {
        // TODO: Implement getFormType() method.
    }

    public function getOptions(array $context = [])
    {
        // TODO: Implement getOptions() method.
    }

    public function getSortOrder()
    {
        // TODO: Implement getSortOrder() method.
    }

    /**
     * @param ShippingContextAwareInterface $dataEntity
     * @param ShippingRuleConfiguration $configEntity
     * @return Price
     */
    public function calculatePrice(
        ShippingContextAwareInterface $dataEntity,
        ShippingRuleConfiguration $configEntity
    ) {
        // TODO: Implement calculatePrice() method.
    }

}