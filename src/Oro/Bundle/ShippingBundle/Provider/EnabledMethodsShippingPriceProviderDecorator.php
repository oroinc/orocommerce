<?php
namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

class EnabledMethodsShippingPriceProviderDecorator implements ShippingPriceProviderInterface
{
    /**
     * @var ShippingPriceProviderInterface $provider
     */
    protected $provider;

    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    /**
     * @param ShippingPriceProviderInterface
     * @param ShippingMethodRegistry $registry
     */
    public function __construct(ShippingPriceProviderInterface $provider, ShippingMethodRegistry $registry)
    {
        $this->provider = $provider;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableMethodsViews(ShippingContextInterface $context)
    {
        $methodViewCollection = clone $this->provider->getApplicableMethodsViews($context);
        $methodViews = $methodViewCollection->getAllMethodsViews();
        foreach ($methodViews as $methodId => $methodView) {
            $method = $this->registry->getShippingMethod($methodId);
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
