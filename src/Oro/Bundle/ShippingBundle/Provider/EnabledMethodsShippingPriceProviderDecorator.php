<?php
namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class EnabledMethodsShippingPriceProviderDecorator
{
    /**
     * @var ShippingPriceProviderInterface $provider
     */
    protected $provider;

    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject $registry
     */
    protected $registry;

    /**
     * @param ShippingPriceProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider
     * @param ShippingMethodRegistry $registry
     */
    public function __construct($provider, ShippingMethodRegistry $registry)
    {
        $this->provider = $provider;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableMethodsViews(ShippingContextInterface $context)
    {
        /** var ShippingMethodViewCollection $methodCollection */
        $methodViewCollection = $this->provider->getApplicableMethodsViews($context);

        /** @var array $methodViews */
        $methodViews = $methodViewCollection->getAllMethodsViews();
        foreach ($methodViews as $methodId => $methodView) {
            $method = $this->registry->getShippingMethod($methodId);
            if (!$method->isEnabled()) {
                $methodViewCollection->removeMethodView($methodId);
            }
        }

        return $methodViewCollection;
    }
}
