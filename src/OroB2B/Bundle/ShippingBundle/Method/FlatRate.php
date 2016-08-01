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
        return "Flat Rate";
    }

    public function getShippingTypes()
    {
        return ['per_item', 'per_order'];
    }

    public function getShippingTypeLabel($type)
    {
        $labels = [
            'per_item' => 'Per Item',
            'per_order' => 'Per Order'
        ];
        if (in_array($type, $this->getShippingTypes())) {
            return $labels[$type];
        } else {
            return null;
        }
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
        return $configEntity->getPrice();
    }
}
