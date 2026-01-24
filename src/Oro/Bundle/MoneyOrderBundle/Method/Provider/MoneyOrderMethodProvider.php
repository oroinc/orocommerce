<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Provider;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProviderInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderPaymentMethodFactoryInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;

/**
 * Provides money order payment methods from configuration.
 *
 * Collects and registers money order payment methods based on configuration,
 * making them available for use in the payment system.
 */
class MoneyOrderMethodProvider extends AbstractPaymentMethodProvider
{
    /**
     * @var MoneyOrderPaymentMethodFactoryInterface
     */
    protected $factory;

    /**
     * @var MoneyOrderConfigProviderInterface
     */
    private $configProvider;

    public function __construct(
        MoneyOrderConfigProviderInterface $configProvider,
        MoneyOrderPaymentMethodFactoryInterface $factory
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
            $this->addMoneyOrderMethod($config);
        }
    }

    protected function addMoneyOrderMethod(MoneyOrderConfigInterface $config)
    {
        $this->addMethod(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
