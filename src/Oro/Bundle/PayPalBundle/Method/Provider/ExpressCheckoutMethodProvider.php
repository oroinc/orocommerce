<?php

namespace Oro\Bundle\PayPalBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\PayPalExpressCheckoutPaymentMethodFactoryInterface;

/**
 * Provides PayPal Express Checkout payment method instances.
 *
 * Collects and manages Express Checkout payment methods from configuration,
 * creating method instances using the configured factory.
 */
class ExpressCheckoutMethodProvider extends AbstractPaymentMethodProvider
{
    const TYPE = 'express_checkout';

    /**
     * @var PayPalExpressCheckoutConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var PayPalExpressCheckoutPaymentMethodFactoryInterface
     */
    protected $factory;

    public function __construct(
        PayPalExpressCheckoutConfigProviderInterface $configProvider,
        PayPalExpressCheckoutPaymentMethodFactoryInterface $factory
    ) {
        parent::__construct();
        $this->configProvider = $configProvider;
        $this->factory = $factory;
    }

    #[\Override]
    protected function collectMethods()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addCreditCardMethod($config);
        }
    }

    protected function addCreditCardMethod(PayPalExpressCheckoutConfigInterface $config)
    {
        $this->addMethod(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
