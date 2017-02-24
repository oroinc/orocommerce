<?php

namespace Oro\Bundle\FlatRateShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FlatRateShippingBundle\Form\Type\FlatRateOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class FlatRateMethodType implements ShippingMethodTypeInterface
{
    const IDENTIFIER = 'primary';

    const PRICE_OPTION = 'price';
    const TYPE_OPTION = 'type';
    const HANDLING_FEE_OPTION = 'handling_fee';

    const PER_ORDER_TYPE = 'per_order';
    const PER_ITEM_TYPE = 'per_item';

    /** @var string */
    protected $label;

    /**
     * @param string $label
     */
    public function __construct($label)
    {
        $this->label = $label;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return static::IDENTIFIER;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return FlatRateOptionsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrice(ShippingContextInterface $context, array $methodOptions, array $typeOptions)
    {
        $price = $typeOptions[static::PRICE_OPTION];
        switch ($typeOptions[static::TYPE_OPTION]) {
            case static::PER_ORDER_TYPE:
                break;
            case static::PER_ITEM_TYPE:
                $countItems = array_sum(array_map(function (ShippingLineItemInterface $item) {
                    return $item->getQuantity();
                }, $context->getLineItems()->toArray()));
                $price = $countItems * (float)$price;
                break;
            default:
                return null;
        }

        $handlingFee = 0;
        if (array_key_exists(static::HANDLING_FEE_OPTION, $typeOptions)
            && $typeOptions[static::HANDLING_FEE_OPTION]
        ) {
            $handlingFee = $typeOptions[static::HANDLING_FEE_OPTION];
        }

        return Price::create((float)$price + (float)$handlingFee, $context->getCurrency());
    }
}
