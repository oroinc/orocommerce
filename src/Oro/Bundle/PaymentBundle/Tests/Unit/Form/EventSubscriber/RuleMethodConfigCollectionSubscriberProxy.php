<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Symfony\Component\Form\FormFactoryInterface;

class RuleMethodConfigCollectionSubscriberProxy extends RuleMethodConfigCollectionSubscriber
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

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
     * @param PaymentMethodProviderInterface $paymentMethodProvider
     * @return $this
     */
    public function setMethodRegistry(PaymentMethodProviderInterface $paymentMethodProvider)
    {
        $this->paymentMethodProvider = $paymentMethodProvider;
        return $this;
    }
}
