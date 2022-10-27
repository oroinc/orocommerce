<?php

namespace Oro\Bundle\PayPalBundle\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\View\Factory\PayPalExpressCheckoutPaymentMethodViewFactoryInterface;

class ExpressCheckoutMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var PayPalExpressCheckoutPaymentMethodViewFactoryInterface */
    private $factory;

    /** @var PayPalExpressCheckoutConfigProviderInterface */
    private $configProvider;

    public function __construct(
        PayPalExpressCheckoutPaymentMethodViewFactoryInterface $factory,
        PayPalExpressCheckoutConfigProviderInterface $configProvider
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;

        parent::__construct();
    }

    protected function buildViews()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addExpressCheckoutView($config);
        }
    }

    protected function addExpressCheckoutView(PayPalExpressCheckoutConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
