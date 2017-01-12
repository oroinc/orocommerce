<?php

namespace Oro\Bundle\PayPalBundle\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Method\Provider\PayPalExpressCheckoutConfigProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView;

class ExpressCheckoutMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var PayPalExpressCheckoutConfigProviderInterface */
    protected $configProvider;

    /**
     * @param PayPalExpressCheckoutConfigProviderInterface $configProvider
     */
    public function __construct(PayPalExpressCheckoutConfigProviderInterface $configProvider)
    {
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

    /**
     * @param PayPalExpressCheckoutConfigInterface $config
     */
    protected function addExpressCheckoutView(PayPalExpressCheckoutConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->buildView($config)
        );
    }

    /**
     * @param PayPalExpressCheckoutConfigInterface $config
     *
     * @return PayPalExpressCheckoutPaymentMethodView
     */
    protected function buildView(PayPalExpressCheckoutConfigInterface $config)
    {
        return new PayPalExpressCheckoutPaymentMethodView($config);
    }
}
