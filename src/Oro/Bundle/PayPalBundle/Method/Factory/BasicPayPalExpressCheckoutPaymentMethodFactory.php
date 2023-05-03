<?php

namespace Oro\Bundle\PayPalBundle\Method\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Factory to create instance of PayPal payment method.
 */
class BasicPayPalExpressCheckoutPaymentMethodFactory implements PayPalExpressCheckoutPaymentMethodFactoryInterface
{
    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var OptionsProviderInterface
     */
    private $optionsProvider;

    /**
     * @var SurchargeProvider
     */
    private $surchargeProvider;

    /**
     * @var PropertyAccessorInterface $propertyAccessor
     */
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
     * @param PayPalExpressCheckoutConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(PayPalExpressCheckoutConfigInterface $config)
    {
        return new PayPalExpressCheckoutPaymentMethod(
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
