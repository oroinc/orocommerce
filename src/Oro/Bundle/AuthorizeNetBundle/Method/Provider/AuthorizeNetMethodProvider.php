<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Provider;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\Factory\AuthorizeNetPaymentMethodFactoryInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\Provider\AuthorizeNetConfigProviderInterface;

class AuthorizeNetMethodProvider extends AbstractPaymentMethodProvider
{
    /**
     * @var AuthorizeNetPaymentMethodFactoryInterface
     */
    private $factory;

    /**
     * @var AuthorizeNetConfigProviderInterface
     */
    private $configProvider;

    /**
     * @param AuthorizeNetConfigProviderInterface $configProvider
     * @param AuthorizeNetPaymentMethodFactoryInterface $factory
     */
    public function __construct(
        AuthorizeNetConfigProviderInterface $configProvider,
        AuthorizeNetPaymentMethodFactoryInterface $factory
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

    /**
     * @param AuthorizeNetConfigInterface $config
     */
    protected function addCreditCardMethod(AuthorizeNetConfigInterface $config)
    {
        $this->addMethod(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
