<?php
namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

/**
 * The decorator of the a shipping price provider that returns views for enabled shipping methods only.
 */
class EnabledMethodsShippingPriceProviderDecorator implements ShippingPriceProviderInterface
{
    private ShippingPriceProviderInterface $innerProvider;
    private ShippingMethodProviderInterface $shippingMethodProvider;

    public function __construct(
        ShippingPriceProviderInterface $innerProvider,
        ShippingMethodProviderInterface $shippingMethodProvider
    ) {
        $this->innerProvider = $innerProvider;
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getApplicableMethodsViews(ShippingContextInterface $context): ShippingMethodViewCollection
    {
        $methodViewCollection = clone $this->innerProvider->getApplicableMethodsViews($context);
        $methodViews = $methodViewCollection->getAllMethodsViews();
        foreach ($methodViews as $methodId => $methodView) {
            $method = $this->shippingMethodProvider->getShippingMethod($methodId);
            if (null !== $method && !$method->isEnabled()) {
                $methodViewCollection->removeMethodView($methodId);
            }
        }

        return $methodViewCollection;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(ShippingContextInterface $context, ?string $methodId, ?string $typeId): ?Price
    {
        return $this->innerProvider->getPrice($context, $methodId, $typeId);
    }
}
