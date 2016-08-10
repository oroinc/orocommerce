<?php

namespace OroB2B\Bundle\ShippingBundle\Method;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Form\Type\FlatRateShippingConfigurationType;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

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
        return '';
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
        if (!($configEntity instanceof FlatRateRuleConfiguration) ||
            ($configEntity->getPrice() === null) ||
            ($configEntity->getType() === null)
        ) {
            return null;
        }

        /** @var FlatRateRuleConfiguration $configEntity */
        /** @var string $currency */
        $currency = $configEntity->getCurrency();
        /** @var Price|null $price */
        $price = $configEntity->getPrice();
        /** @var string $shippingRuleType */
        $shippingRuleType = $configEntity->getType();
        /** @var Price $handlingFee */
        $handlingFee = $configEntity->getHandlingFeeValue();

        if ($shippingRuleType === FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER) {
            return $this->calculatePricePerOrder($price, $handlingFee, $currency);
        }

        return $this->calculatePricePerItem($context, $price, $handlingFee, $currency);
    }

    /**
     * @param Price|null $price
     * @param Price $handlingFee
     * @param string $currency
     * @return Price
     */
    protected function calculatePricePerOrder($price, $handlingFee, $currency)
    {
        return Price::create((float)$price->getValue() + (float)$handlingFee, $currency);
    }

    /**
     * @param ShippingContextAwareInterface $context
     * @param Price|null $price
     * @param Price $handlingFee
     * @param string $currency
     * @return Price
     */
    protected function calculatePricePerItem($context, $price, $handlingFee, $currency)
    {
        /** @var array $context */
        $shippingContext = $context->getShippingContext();

        if (!isset($shippingContext['line_items'])) {
            return null;
        }

        /** @var ArrayCollection $items */
        $items = $shippingContext['line_items'];
        $countItems = 0;

        /** @var LineItem $item */
        foreach ($items as $item) {
            $countItems += $item->getQuantity();
        }

        return Price::create($countItems * (float)$price->getValue() + (float)$handlingFee, $currency);
    }
}
