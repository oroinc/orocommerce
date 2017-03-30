<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\View\Provider;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\Provider\AuthorizeNetConfigProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\AuthorizeNetBundle\Method\View\Factory\AuthorizeNetPaymentMethodViewFactoryInterface;

class AuthorizeNetMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var AuthorizeNetPaymentMethodViewFactoryInterface*/
    private $factory;

    /** @var AuthorizeNetConfigProviderInterface */
    private $configProvider;

    /**
     * @param AuthorizeNetPaymentMethodViewFactoryInterface $factory
     * @param AuthorizeNetConfigProviderInterface $configProvider
     */
    public function __construct(
        AuthorizeNetPaymentMethodViewFactoryInterface $factory,
        AuthorizeNetConfigProviderInterface $configProvider
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

    /**
     * @param AuthorizeNetConfigInterface $config
     */
    protected function addCreditCardView(AuthorizeNetConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
