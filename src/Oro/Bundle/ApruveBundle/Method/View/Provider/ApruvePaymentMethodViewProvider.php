<?php

namespace Oro\Bundle\ApruveBundle\Method\View\Provider;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\Config\Provider\ApruveConfigProviderInterface;
use Oro\Bundle\ApruveBundle\Method\View\Factory\ApruvePaymentMethodViewFactoryInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;

class ApruvePaymentMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /**
     * @var ApruvePaymentMethodViewFactoryInterface
     */
    private $factory;

    /**
     * @var ApruveConfigProviderInterface
     */
    private $configProvider;

    /**
     * @param ApruveConfigProviderInterface           $configProvider
     * @param ApruvePaymentMethodViewFactoryInterface $factory
     */
    public function __construct(
        ApruveConfigProviderInterface $configProvider,
        ApruvePaymentMethodViewFactoryInterface $factory
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function buildViews()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addPaymentMethodView($config);
        }
    }

    /**
     * @param ApruveConfigInterface $config
     */
    protected function addPaymentMethodView(ApruveConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
