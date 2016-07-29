<?php

namespace OroB2B\Bundle\ShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;

class FlatRate implements ShippingMethodInterface
{
    const NAME = 'flat_rate';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return "Flat Rate";
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingTypes()
    {
        return ['per_item', 'per_order'];
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingTypeLabel($type)
    {
        $labels = [
            'per_item' => 'Per Item',
            'per_order' => 'Per Order'
        ];
        if (in_array($type, $this->getShippingTypes(), true)) {
            return $labels[$type];
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        // TODO: Implement getFormType() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(array $context = [])
    {
        // TODO: Implement getOptions() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        // TODO: Implement getSortOrder() method.
    }

    /**
     * @param ShippingContextAwareInterface $context
     * @param FlatRateRuleConfiguration|ShippingRuleConfiguration $configEntity
     * @return null|Price
     */
    public function calculatePrice(ShippingContextAwareInterface $context, ShippingRuleConfiguration $configEntity)
    {
        return $configEntity->getPrice();
    }
}
