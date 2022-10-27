<?php

namespace Oro\Bundle\PayPalBundle\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\View\Factory\PayPalCreditCardPaymentMethodViewFactoryInterface;

class CreditCardMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var PayPalCreditCardPaymentMethodViewFactoryInterface */
    private $factory;

    /** @var PayPalCreditCardConfigProviderInterface */
    private $configProvider;

    public function __construct(
        PayPalCreditCardPaymentMethodViewFactoryInterface $factory,
        PayPalCreditCardConfigProviderInterface $configProvider
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;

        parent::__construct();
    }

    protected function buildViews()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addCreditCardView($config);
        }
    }

    protected function addCreditCardView(PayPalCreditCardConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
