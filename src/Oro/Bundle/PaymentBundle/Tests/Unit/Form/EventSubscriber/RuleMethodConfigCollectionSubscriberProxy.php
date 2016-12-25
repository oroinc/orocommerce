<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Symfony\Component\Form\FormFactoryInterface;

class RuleMethodConfigCollectionSubscriberProxy extends RuleMethodConfigCollectionSubscriber
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var PaymentMethodRegistry
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
    public function setFactory(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @param PaymentMethodRegistry $methodRegistry
     * @return $this
     */
    public function setMethodRegistry(PaymentMethodRegistry $methodRegistry)
    {
        $this->methodRegistry = $methodRegistry;
        return $this;
    }
}
