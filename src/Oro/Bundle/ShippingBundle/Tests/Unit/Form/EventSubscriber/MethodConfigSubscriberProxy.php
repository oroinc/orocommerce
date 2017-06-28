<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodConfigSubscriber;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class MethodConfigSubscriberProxy extends MethodConfigSubscriber implements
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
