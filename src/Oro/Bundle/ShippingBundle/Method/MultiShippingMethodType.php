<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Multi Shipping method type implementation.
 */
class MultiShippingMethodType implements ShippingMethodTypeInterface
{
    public const IDENTIFIER = 'primary';

    private string $label;
    private RoundingServiceInterface $roundingService;
    private MultiShippingCostProvider $shippingCostProvider;

    public function __construct(
        string $label,
        RoundingServiceInterface $roundingService,
        MultiShippingCostProvider $shippingCostProvider
    ) {
        $this->label = $label;
        $this->roundingService = $roundingService;
        $this->shippingCostProvider = $shippingCostProvider;
    }

    public function getIdentifier(): string
    {
        return static::IDENTIFIER;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSortOrder(): int
    {
        return 0;
    }

    public function getOptionsConfigurationFormType(): string
    {
        return HiddenType::class;
    }

    public function calculatePrice(
        ShippingContextInterface $context,
        array $methodOptions,
        array $typeOptions
    ): ?Price {
        if (!$context->getSourceEntity() instanceof Checkout) {
            return null;
        }

        $value = $this->shippingCostProvider->getCalculatedMultiShippingCost($context->getSourceEntity());

        return Price::create($this->roundingService->round($value), $context->getCurrency());
    }
}