<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;

interface ConfigSubscriberProxyInterface extends EventSubscriberInterface
{
    /**
     * @param FormFactoryInterface $factory
     *
     * @return ConfigSubscriberProxyInterface
     */
    public function setFactory(FormFactoryInterface $factory);


    /**
     * @param ShippingMethodRegistry $methodRegistry
     *
     * @return ConfigSubscriberProxyInterface
     */
    public function setMethodRegistry(ShippingMethodRegistry $methodRegistry);
}
