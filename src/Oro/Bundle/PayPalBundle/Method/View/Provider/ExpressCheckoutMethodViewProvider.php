<?php

namespace Oro\Bundle\PayPalBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView;

class ExpressCheckoutMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var PaymentConfigProviderInterface */
    protected $configProvider;

    /**
     * @param PaymentConfigProviderInterface $configProvider
     */
    public function __construct(PaymentConfigProviderInterface $configProvider)
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
     * @param PaymentConfigInterface $config
     */
    protected function addExpressCheckoutView(PaymentConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->buildView($config)
        );
    }

    /**
     * @param PaymentConfigInterface $config
     *
     * @return PayPalExpressCheckoutPaymentMethodView
     */
    protected function buildView(PaymentConfigInterface $config)
    {
        return new PayPalExpressCheckoutPaymentMethodView($config);
    }
}
