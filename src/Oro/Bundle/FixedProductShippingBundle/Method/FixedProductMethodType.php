<?php

namespace Oro\Bundle\FixedProductShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Form\Type\FixedProductOptionsType;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

/**
 * Represents Fixed Product shipping method type.
 */
class FixedProductMethodType implements ShippingMethodTypeInterface
{
    public const IDENTIFIER = 'primary';

    // Fields
    public const SURCHARGE_AMOUNT = 'surcharge_amount';
    public const SURCHARGE_TYPE = 'surcharge_type';
    public const SURCHARGE_ON = 'surcharge_on';

    // Surcharge Type Options
    public const PERCENT = 'percent';
    public const FIXED_AMOUNT = 'fixed_amount';

    // Surcharge On Options
    public const PRODUCT_PRICE = 'product_price';
    public const PRODUCT_SHIPPING_COST = 'product_shipping_cost';

    private string $label;
    private RoundingServiceInterface $roundingService;
    private ShippingCostProvider $shippingCostProvider;

    public function __construct(
        string $label,
        RoundingServiceInterface $roundingService,
        ShippingCostProvider $shippingCostProvider
    ) {
        $this->label = $label;
        $this->roundingService = $roundingService;
        $this->shippingCostProvider = $shippingCostProvider;
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
        return FixedProductOptionsType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePrice(
        ShippingContextInterface $context,
        array $methodOptions,
        array $typeOptions
    ): ?Price {
        $subtotal = $context->getSubtotal();
        if (!$subtotal->getValue()) {
            return null;
        }

        $surchargeAmount = (float)$typeOptions[self::SURCHARGE_AMOUNT];
        $shippingCost = $this->shippingCostProvider->getCalculatedProductShippingCost(
            $context->getLineItems(),
            $context->getCurrency()
        );

        if ($this->isPercentType($typeOptions)) {
            $value = match ($typeOptions[self::SURCHARGE_ON]) {
                self::PRODUCT_PRICE => $shippingCost + ($subtotal->getValue() * ($surchargeAmount / 100)),
                self::PRODUCT_SHIPPING_COST => $shippingCost + ($shippingCost * ($surchargeAmount / 100)),
            };
        } else {
            $value = $shippingCost + $surchargeAmount;
        }

        return Price::create($this->roundingService->round($value), $context->getCurrency());
    }

    private function isPercentType(array $typeOptions): bool
    {
        return $typeOptions[self::SURCHARGE_TYPE] === self::PERCENT;
    }
}
