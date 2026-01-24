<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalExpressCheckoutConfigFactoryInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides PayPal Express Checkout payment method configurations.
 *
 * Retrieves and caches Express Checkout configuration objects for available payment methods,
 * supporting lookup by payment method identifier.
 */
class PayPalExpressCheckoutConfigProvider extends AbstractPayPalConfigProvider implements
    PayPalExpressCheckoutConfigProviderInterface
{
    /**
     * @var PayPalExpressCheckoutConfigInterface[]
     */
    protected $configs = [];

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        PayPalExpressCheckoutConfigFactoryInterface $factory,
        $type
    ) {
        parent::__construct($doctrine, $logger, $factory, $type);
    }

    /**
     * @return PayPalExpressCheckoutConfigInterface[]
     */
    #[\Override]
    public function getPaymentConfigs()
    {
        if (0 === count($this->configs)) {
            return $this->configs = $this->collectConfigs();
        }

        return $this->configs;
    }

    /**
     * @param string $identifier
     * @return PayPalExpressCheckoutConfigInterface|null
     */
    #[\Override]
    public function getPaymentConfig($identifier)
    {
        if (!$this->hasPaymentConfig($identifier)) {
            return null;
        }

        $configs = $this->getPaymentConfigs();

        return $configs[$identifier];
    }
}
