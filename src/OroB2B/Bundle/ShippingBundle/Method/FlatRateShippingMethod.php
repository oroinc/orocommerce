<?php

namespace OroB2B\Bundle\ShippingBundle\Method;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Form\Type\FlatRateShippingConfigurationType;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;

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
    public function getLabel()
    {
        return "Flat Rate";
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingTypes()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRuleConfigurationClass()
    {
        return FlatRateRuleConfiguration::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingTypeLabel($type)
    {
        $labels = [
            FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ITEM => 'Per Item',
            FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER => 'Per Order'
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
        return FlatRateShippingConfigurationType::NAME;
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
     * @param ShippingRuleConfiguration $configEntity
     * @return null|Price
     */
    public function calculatePrice(
        ShippingContextAwareInterface $context,
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
        $handlingFee = $configEntity->getHandlingFeeValue();

        if (empty($price) || empty($shippingRuleType)) {
            return null;
        }

        if ($shippingRuleType == FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER) {
            return Price::create((float)$price->getValue() + (float)$handlingFee, $currency);
        } else {
            /** @var array $context */
            $shippingContext = $context->getShippingContext();

            if (empty($shippingContext) ||
                !array_key_exists('checkout', $shippingContext) ||
                empty($shippingContext['checkout'])
            ) {
                return null;
            }

            /** @var Checkout|null $checkout */
            $checkout = $shippingContext['checkout'];
            /** @var ArrayCollection|null $items */
            $items = $checkout->getLineItems();
            $countItems = !empty($items) && ($items instanceof ArrayCollection) ? $items->count() : 0;

            return Price::create($countItems * (float)$price->getValue() + (float)$handlingFee, $currency);
        }
    }
}
