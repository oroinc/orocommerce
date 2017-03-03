<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Provider;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProviderInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderPaymentMethodFactoryInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;

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

    /**
     * @param MoneyOrderConfigProviderInterface $configProvider
     * @param MoneyOrderPaymentMethodFactoryInterface $factory
     */
    public function __construct(
        MoneyOrderConfigProviderInterface $configProvider,
        MoneyOrderPaymentMethodFactoryInterface $factory
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
            $this->addMoneyOrderMethod($config);
        }
    }

    /**
     * @param MoneyOrderConfigInterface $config
     */
    protected function addMoneyOrderMethod(MoneyOrderConfigInterface $config)
    {
        $this->addMethod(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
