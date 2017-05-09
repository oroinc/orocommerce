<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\View\Provider;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\Provider\AuthorizeNetConfigProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\AuthorizeNetBundle\Method\View\Factory\AuthorizeNetPaymentMethodViewFactoryInterface;

class AuthorizeNetMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var AuthorizeNetPaymentMethodViewFactoryInterface*/
    private $paymentMethodFactory;

    /** @var AuthorizeNetConfigProviderInterface */
    private $configProvider;

    /**
     * @param AuthorizeNetPaymentMethodViewFactoryInterface $paymentMethodFactory
     * @param AuthorizeNetConfigProviderInterface $configProvider
     */
    public function __construct(
        AuthorizeNetPaymentMethodViewFactoryInterface $paymentMethodFactory,
        AuthorizeNetConfigProviderInterface $configProvider
    ) {
        $this->paymentMethodFactory = $paymentMethodFactory;
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
            $this->addPaymentMethodView($config);
        }
    }

    /**
     * @param AuthorizeNetConfigInterface $config
     */
    protected function addPaymentMethodView(AuthorizeNetConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->paymentMethodFactory->create($config)
        );
    }
}
