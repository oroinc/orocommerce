<?php

namespace Oro\Bundle\PayPalBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Method\AbstractPaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\Routing\RouterInterface;

class ExpressCheckoutMethodProvider extends AbstractPaymentMethodProvider
{
    const TYPE = 'express_checkout';
    
    /**
     * @var Gateway
     */
    protected $gateway;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PayPalExpressCheckoutConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var ExtractOptionsProvider
     */
    protected $optionsProvider;

    /**
     * @var SurchargeProvider
     */
    protected $surchargeProvider;

    /**
     * @param Gateway $gateway
     * @param PayPalExpressCheckoutConfigProviderInterface $configProvider
     * @param RouterInterface $router
     * @param DoctrineHelper $doctrineHelper
     * @param ExtractOptionsProvider $optionsProvider
     * @param SurchargeProvider $surchargeProvider
     */
    public function __construct(
        Gateway $gateway,
        PayPalExpressCheckoutConfigProviderInterface $configProvider,
        RouterInterface $router,
        DoctrineHelper $doctrineHelper,
        ExtractOptionsProvider $optionsProvider,
        SurchargeProvider $surchargeProvider
    ) {
        parent::__construct();
        $this->gateway = $gateway;
        $this->configProvider = $configProvider;
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
        $this->optionsProvider = $optionsProvider;
        $this->surchargeProvider = $surchargeProvider;
    }

    protected function collectMethods()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addCreditCardMethod($config);
        }
    }

    /**
     * @param PayPalExpressCheckoutConfigInterface $config
     */
    protected function addCreditCardMethod(PayPalExpressCheckoutConfigInterface $config)
    {
        $this->addMethod(
            $config->getPaymentMethodIdentifier(),
            $this->buildMethod($config)
        );
    }

    /**
     * @param PayPalExpressCheckoutConfigInterface $config
     *
     * @return PayPalCreditCardPaymentMethod
     */
    protected function buildMethod(PayPalExpressCheckoutConfigInterface $config)
    {
        return new PayPalExpressCheckoutPaymentMethod(
            $this->gateway,
            $config,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider
        );
    }

    /**
     * {inheritDocs}
     */
    public function getType()
    {
        return sprintf('%s_%s', $this->configProvider->getType(), self::TYPE);
    }
}
