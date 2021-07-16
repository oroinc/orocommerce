<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\View\Provider;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProviderInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactoryInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;

class MoneyOrderMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var MoneyOrderPaymentMethodViewFactoryInterface */
    private $factory;

    /** @var MoneyOrderConfigProviderInterface */
    private $configProvider;

    public function __construct(
        MoneyOrderConfigProviderInterface $configProvider,
        MoneyOrderPaymentMethodViewFactoryInterface $factory
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function buildViews()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addMoneyOrderView($config);
        }
    }

    protected function addMoneyOrderView(MoneyOrderConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
