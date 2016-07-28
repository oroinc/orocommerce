<?php

namespace OroB2B\Bundle\ShippingBundle\Method;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
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
     * @return Price|nul
     */
    public function calculatePrice(
        ShippingContextAwareInterface $dataEntity,
        ShippingRuleConfiguration $configEntity
    ) {
        if (!($configEntity instanceof FlatRateRuleConfiguration)) {
            return null;
        }

        /** @var FlatRateRuleConfiguration $configEntity */
        $currency = $configEntity->getCurrency();
        $price = $configEntity->getPrice();
        $shippingRuleType = $configEntity->getType();
        /** @var Price $handlingFee */
        $handlingFee = $configEntity->getHandlingFee();

        if ($shippingRuleType == FlatRateRuleConfiguration::TYPE_PER_ORDER) {
            return Price::create($price + $handlingFee->getValue(), $currency);
        } else {
            /** @var Checkout|null $checkout */
            $checkout = $dataEntity->get('checkout');

            if (!empty($checkout)) {
                /** @var ArrayCollection|null $items */
                $items = $checkout->getLineItems();
                $countItems = !empty($items) && ($items instanceof ArrayCollection) ? $items->count() : 0;

                return Price::create($countItems * $price + $handlingFee->getValue(), $currency);
            }
        }

        return null;
    }
}
