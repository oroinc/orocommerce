<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodConfigSubscriber;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Symfony\Component\Form\FormFactoryInterface;

class MethodConfigSubscriberProxy extends MethodConfigSubscriber
{
    public function __construct()
    {
    }

    public function setFactory(FormFactoryInterface $factory): void
    {
        $this->factory = $factory;
    }

    public function setShippingMethodProvider(ShippingMethodProviderInterface $shippingMethodProvider): void
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }
}
