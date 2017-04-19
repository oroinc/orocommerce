<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodConfigCollectionSubscriber;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Symfony\Component\Form\FormFactoryInterface;

class MethodConfigCollectionSubscriberProxy extends MethodConfigCollectionSubscriber implements
    ConfigSubscriberProxyInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    public function __construct()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function setFactory(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMethodRegistry(ShippingMethodRegistry $methodRegistry)
    {
        $this->methodRegistry = $methodRegistry;
        return $this;
    }
}
