<?php

namespace Oro\Bundle\InfinitePayBundle\Method\View\Provider;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\Provider\InfinitePayConfigProviderInterface;
use Oro\Bundle\InfinitePayBundle\Method\View\Factory\InfinitePayViewFactoryInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;

class InfinitePayViewProvider extends AbstractPaymentMethodViewProvider
{
    /**
     * @var InfinitePayViewFactoryInterface
     */
    private $factory;

    /**
     * @var InfinitePayConfigProviderInterface
     */
    private $configProvider;


    /**
     * @param InfinitePayViewFactoryInterface    $factory
     * @param InfinitePayConfigProviderInterface $configProvider
     */
    public function __construct(
        InfinitePayViewFactoryInterface $factory,
        InfinitePayConfigProviderInterface $configProvider
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function buildViews()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addInfinitePayView($config);
        }
    }

    /**
     * @param InfinitePayConfigInterface $config
     */
    protected function addInfinitePayView(InfinitePayConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
