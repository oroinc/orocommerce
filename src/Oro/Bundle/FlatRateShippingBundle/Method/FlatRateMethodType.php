<?php

namespace Oro\Bundle\FlatRateShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FlatRateShippingBundle\Form\Type\FlatRateOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

/**
 * Represents Flat Rate shipping method type.
 */
class FlatRateMethodType implements ShippingMethodTypeInterface
{
    public const IDENTIFIER = 'primary';

    public const PRICE_OPTION = 'price';
    public const TYPE_OPTION = 'type';
    public const HANDLING_FEE_OPTION = 'handling_fee';

    public const PER_ORDER_TYPE = 'per_order';
    public const PER_ITEM_TYPE = 'per_item';

    private string $label;

    public function __construct(string  $label)
    {
        $this->label = $label;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder(): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType(): ?string
    {
        return FlatRateOptionsType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePrice(
        ShippingContextInterface $context,
        array $methodOptions,
        array $typeOptions
    ): ?Price {
        $price = $typeOptions[self::PRICE_OPTION];
        switch ($typeOptions[self::TYPE_OPTION]) {
            case self::PER_ORDER_TYPE:
                break;
            case self::PER_ITEM_TYPE:
                $countItems = array_sum(array_map(function (ShippingLineItemInterface $item) {
                    return $item->getQuantity();
                }, $context->getLineItems()->toArray()));
                $price = $countItems * (float)$price;
                break;
            default:
                return null;
        }

        $handlingFee = 0;
        if (\array_key_exists(self::HANDLING_FEE_OPTION, $typeOptions)
            && $typeOptions[self::HANDLING_FEE_OPTION]
        ) {
            $handlingFee = $typeOptions[self::HANDLING_FEE_OPTION];
        }

        return Price::create((float)$price + (float)$handlingFee, $context->getCurrency());
    }
}
