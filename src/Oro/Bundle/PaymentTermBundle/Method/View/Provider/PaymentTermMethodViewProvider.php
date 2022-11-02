<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\Factory\PaymentTermPaymentMethodViewFactoryInterface;

class PaymentTermMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /**
     * @var PaymentTermPaymentMethodViewFactoryInterface
     */
    private $factory;

    /**
     * @var PaymentTermConfigProviderInterface
     */
    private $configProvider;

    public function __construct(
        PaymentTermPaymentMethodViewFactoryInterface $factory,
        PaymentTermConfigProviderInterface $configProvider
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
            $this->addPaymentTermView($config);
        }
    }

    protected function addPaymentTermView(PaymentTermConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
