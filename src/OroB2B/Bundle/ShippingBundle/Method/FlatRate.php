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
        return "Flat Rate";
    }

    public function getShippingTypes()
    {
        return [FlatRateRuleConfiguration::TYPE_PER_ORDER, FlatRateRuleConfiguration::TYPE_PER_ITEM];
    }

    public function getShippingTypeLabel($type)
    {
        $labels = [
            FlatRateRuleConfiguration::TYPE_PER_ITEM => 'Per Item',
            FlatRateRuleConfiguration::TYPE_PER_ORDER => 'Per Order'
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
     * @return Price|null
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
        /** @var Price|null $price */
        $price = $configEntity->getPrice();
        /** @var string $shippingRuleType */
        $shippingRuleType = $configEntity->getType();
        /** @var Price $handlingFee */
        $handlingFee = $configEntity->getHandlingFee();

        if (empty($price) || empty($shippingRuleType)) {
            return null;
        }

        if ($shippingRuleType == FlatRateRuleConfiguration::TYPE_PER_ORDER) {
            return Price::create((float)$price->getValue() + (float)$handlingFee->getValue(), $currency);
        } else {
            /** @var array $context */
            $context = $dataEntity->getShippingContext();

            if (empty($context) || !array_key_exists('checkout', $context) || empty($context['checkout'])) {
                return null;
            }

            /** @var Checkout|null $checkout */
            $checkout = $context['checkout'];
            /** @var ArrayCollection|null $items */
            $items = $checkout->getLineItems();
            $countItems = !empty($items) && ($items instanceof ArrayCollection) ? $items->count() : 0;

            return Price::create($countItems * (float)$price->getValue() + (float)$handlingFee->getValue(), $currency);
        }
    }
}
