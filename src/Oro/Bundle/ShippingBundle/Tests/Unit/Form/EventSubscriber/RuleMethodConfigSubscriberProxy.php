<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\RuleMethodConfigSubscriber;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class RuleMethodConfigSubscriberProxy extends RuleMethodConfigSubscriber
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
