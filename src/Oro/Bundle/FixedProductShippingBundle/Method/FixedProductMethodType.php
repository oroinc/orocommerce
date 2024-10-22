<?php

namespace Oro\Bundle\FixedProductShippingBundle\Method;

use Brick\Math\BigDecimal;
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

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSortOrder(): int
    {
        return 0;
    }

    public function getOptionsConfigurationFormType(): ?string
    {
        return FixedProductOptionsType::class;
    }

    public function calculatePrice(
        ShippingContextInterface $context,
        array $methodOptions,
        array $typeOptions
    ): ?Price {
        $currency = $context->getCurrency();
        $subtotalWithShipping = $this->shippingCostProvider
            ->getCalculatedProductShippingCostWithSubtotal(
                $context->getSourceEntity(),
                $context->getLineItems(),
                $currency
            );

        [$subtotal, $shipping] = $subtotalWithShipping;

        $value = $this->recalculateShippingCostWithSurcharge($shipping, $subtotal, $typeOptions);

        return Price::create($this->roundingService->round($value->toFloat()), $currency);
    }

    private function recalculateShippingCostWithSurcharge(
        BigDecimal $shippingCost,
        BigDecimal $price,
        array $typeOptions
    ): BigDecimal {
        $surchargeAmount = BigDecimal::of(
            $typeOptions[FixedProductMethodType::SURCHARGE_AMOUNT] ?? ShippingCostProvider::DEFAULT_COST
        );

        if ($surchargeAmount->isEqualTo(0)) {
            return $shippingCost;
        }

        if ($this->isPercentType($typeOptions)) {
            $surchargeAmount = $surchargeAmount->exactlyDividedBy(100.00);
            $value = match ($typeOptions[FixedProductMethodType::SURCHARGE_ON]) {
                FixedProductMethodType::PRODUCT_PRICE =>
                    $shippingCost->plus($price->multipliedBy($surchargeAmount)),
                FixedProductMethodType::PRODUCT_SHIPPING_COST =>
                    $shippingCost->plus($shippingCost->multipliedBy($surchargeAmount)),
            };
        } else {
            $value = $shippingCost->plus($surchargeAmount);
        }

        return $value;
    }

    private function isPercentType(array $typeOptions): bool
    {
        return $typeOptions[FixedProductMethodType::SURCHARGE_TYPE] === FixedProductMethodType::PERCENT;
    }
}
