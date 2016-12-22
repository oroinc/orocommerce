<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodTypeConfigCollectionSubscriber;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Symfony\Component\Form\FormFactoryInterface;

class MethodTypeConfigCollectionSubscriberProxy extends MethodTypeConfigCollectionSubscriber
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    /**
     * MethodTypeConfigCollectionSubscriberProxy constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param FormFactoryInterface $factory
     * @return $this
     */
    public function setFactory(FormFactoryInterface$factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @param ShippingMethodRegistry $methodRegistry
     * @return $this
     */
    public function setMethodRegistry(ShippingMethodRegistry $methodRegistry)
    {
        $this->methodRegistry = $methodRegistry;
        return $this;
    }
}
