<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\Factory\PaymentTermPaymentMethodFactoryInterface;

/**
 * Provides payment term payment method instances.
 *
 * This provider collects all available payment term configurations and creates corresponding payment method instances,
 * making them available to the payment method system for use in checkout and order processing.
 */
class PaymentTermMethodProvider extends AbstractPaymentMethodProvider
{
    /**
     * @var PaymentTermPaymentMethodFactoryInterface
     */
    protected $factory;

    /**
     * @var PaymentTermConfigProviderInterface
     */
    private $configProvider;

    public function __construct(
        PaymentTermConfigProviderInterface $configProvider,
        PaymentTermPaymentMethodFactoryInterface $factory
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
            $this->addPaymentTermMethod($config);
        }
    }

    protected function addPaymentTermMethod(PaymentTermConfigInterface $config)
    {
        $this->addMethod(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
