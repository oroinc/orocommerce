<?php
namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

class EnabledMethodsShippingPriceProviderDecorator implements ShippingPriceProviderInterface
{
    /**
     * @var ShippingPriceProviderInterface
     */
    protected $provider;

    /**
     * @var ShippingMethodProviderInterface
     */
    protected $shippingMethodProvider;

    public function __construct(
        ShippingPriceProviderInterface $provider,
        ShippingMethodProviderInterface $shippingMethodProvider
    ) {
        $this->provider = $provider;
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableMethodsViews(ShippingContextInterface $context)
    {
        $methodViewCollection = clone $this->provider->getApplicableMethodsViews($context);
        $methodViews = $methodViewCollection->getAllMethodsViews();
        foreach ($methodViews as $methodId => $methodView) {
            $method = $this->shippingMethodProvider->getShippingMethod($methodId);
            if (!$method->isEnabled()) {
                $methodViewCollection->removeMethodView($methodId);
            }
        }

        return $methodViewCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice(ShippingContextInterface $context, $methodId, $typeId)
    {
        return $this->provider->getPrice($context, $methodId, $typeId);
    }
}
