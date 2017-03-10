<?php

namespace Oro\Bundle\PayPalBundle\Method\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;

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
     * @var ExtractOptionsProvider
     */
    private $optionsProvider;

    /**
     * @var SurchargeProvider
     */
    private $surchargeProvider;

    /**
     * @var PropertyAccessor $propertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param Gateway $gateway
     * @param RouterInterface $router
     * @param DoctrineHelper $doctrineHelper
     * @param ExtractOptionsProvider $optionsProvider
     * @param SurchargeProvider $surchargeProvider
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        Gateway $gateway,
        RouterInterface $router,
        DoctrineHelper $doctrineHelper,
        ExtractOptionsProvider $optionsProvider,
        SurchargeProvider $surchargeProvider,
        PropertyAccessor $propertyAccessor
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
