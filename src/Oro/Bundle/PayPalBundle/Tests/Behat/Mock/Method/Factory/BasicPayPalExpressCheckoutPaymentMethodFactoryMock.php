<?php

namespace Oro\Bundle\PayPalBundle\Tests\Behat\Mock\Method\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\BasicPayPalExpressCheckoutPaymentMethodFactory;
use Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\Tests\Behat\Mock\Method\PayPalExpressCheckoutPaymentMethodMock;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;

class BasicPayPalExpressCheckoutPaymentMethodFactoryMock extends BasicPayPalExpressCheckoutPaymentMethodFactory
{
    /** @var Gateway */
    private $gateway;

    /** @var RouterInterface */
    private $router;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var OptionsProviderInterface */
    private $optionsProvider;

    /** @var SurchargeProvider */
    private $surchargeProvider;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    public function __construct(
        Gateway $gateway,
        RouterInterface $router,
        DoctrineHelper $doctrineHelper,
        OptionsProviderInterface $optionsProvider,
        SurchargeProvider $surchargeProvider,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->gateway = $gateway;
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
        $this->optionsProvider = $optionsProvider;
        $this->surchargeProvider = $surchargeProvider;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PayPalExpressCheckoutConfigInterface $config)
    {
        return new PayPalExpressCheckoutPaymentMethodMock(
            $this->gateway,
            $config,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider,
            $this->propertyAccessor
        );
    }
}
