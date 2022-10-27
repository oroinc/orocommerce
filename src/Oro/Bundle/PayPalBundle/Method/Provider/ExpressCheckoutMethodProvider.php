<?php

namespace Oro\Bundle\PayPalBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\PayPalExpressCheckoutPaymentMethodFactoryInterface;

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
