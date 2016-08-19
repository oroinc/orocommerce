<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\EventSubscriber;

use Symfony\Component\Form\FormFactoryInterface;

use OroB2B\Bundle\ShippingBundle\Form\EventSubscriber\RuleConfigurationSubscriber;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class SubscriberProxy extends RuleConfigurationSubscriber
{
    /**
     * @var RuleConfigurationSubscriber
     */
    protected $subscriber;

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

    /**
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, array $arguments)
    {
        if ($this->subscriber) {
            $this->subscriber = new RuleConfigurationSubscriber($this->factory, $this->methodRegistry);
        }
        call_user_func_array([$this->subscriber, $name], $arguments);
    }
}
