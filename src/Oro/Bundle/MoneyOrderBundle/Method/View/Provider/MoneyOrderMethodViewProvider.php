<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\View\Provider;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProviderInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactoryInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;

/**
 * Provides Money Order payment method views for rendering in the storefront.
 *
 * This provider builds and manages Money Order payment method views by retrieving configurations
 * from the config provider and creating view instances using the view factory. It extends the
 * abstract payment method view provider to integrate Money Order views into the payment system's
 * view management infrastructure.
 */
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

    #[\Override]
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
