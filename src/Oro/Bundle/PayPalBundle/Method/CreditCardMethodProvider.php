<?php

namespace Oro\Bundle\PayPalBundle\Method;

use Oro\Bundle\PaymentBundle\Method\AbstractPaymentMethodProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\Routing\RouterInterface;

class CreditCardMethodProvider extends AbstractPaymentMethodProvider
{
    /**
     * @var Gateway
     */
    protected $gateway;

    /**
     * @var PayPalCreditCardConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param Gateway $gateway
     * @param PayPalCreditCardConfigProviderInterface $configProvider
     * @param RouterInterface $router
     */
    public function __construct(
        Gateway $gateway,
        PayPalCreditCardConfigProviderInterface $configProvider,
        RouterInterface $router
    ) {
        parent::__construct();
        $this->gateway = $gateway;
        $this->configProvider = $configProvider;
        $this->router = $router;
    }

    /**
     * {inheritDocs}
     */
    protected function collectMethods()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addCreditCardMethod($config);
        }
    }

    /**
     * @param PayPalCreditCardConfigInterface $config
     */
    protected function addCreditCardMethod(PayPalCreditCardConfigInterface $config)
    {
        $this->addMethod(
            $config->getPaymentMethodIdentifier(),
            $this->buildMethod($config)
        );
    }

    /**
     * @param PayPalCreditCardConfigInterface $config
     *
     * @return PayPalCreditCardPaymentMethod
     */
    protected function buildMethod(PayPalCreditCardConfigInterface $config)
    {
        return new PayPalCreditCardPaymentMethod(
            $this->gateway,
            $config,
            $this->router
        );
    }

    /**
     * {inheritDocs}
     */
    public function getType()
    {
        return $this->configProvider->getType();
    }
}
