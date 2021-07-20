<?php

namespace Oro\Bundle\PayPalBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\Factory\PayPalCreditCardPaymentMethodFactoryInterface;

class CreditCardMethodProvider extends AbstractPaymentMethodProvider
{
    /**
     * @var PayPalCreditCardPaymentMethodFactoryInterface
     */
    private $factory;

    /**
     * @var PayPalCreditCardConfigProviderInterface
     */
    private $configProvider;

    public function __construct(
        PayPalCreditCardConfigProviderInterface $configProvider,
        PayPalCreditCardPaymentMethodFactoryInterface $factory
    ) {
        parent::__construct();
        $this->configProvider = $configProvider;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    protected function collectMethods()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addCreditCardMethod($config);
        }
    }

    protected function addCreditCardMethod(PayPalCreditCardConfigInterface $config)
    {
        $this->addMethod(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
