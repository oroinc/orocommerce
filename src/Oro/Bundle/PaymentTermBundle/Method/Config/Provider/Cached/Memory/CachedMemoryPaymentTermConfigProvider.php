<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Cached\Memory;

use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;

/**
 * Caches payment term configurations in memory for improved performance.
 *
 * This provider wraps another {@see PaymentTermConfigProviderInterface} and caches the retrieved configurations
 * in memory, avoiding repeated lookups during a single request lifecycle.
 */
class CachedMemoryPaymentTermConfigProvider implements PaymentTermConfigProviderInterface
{
    /**
     * @var array|null
     */
    private $cachedConfigs;

    /**
     * @var PaymentTermConfigProviderInterface
     */
    private $paymentTermConfigProvider;

    public function __construct(PaymentTermConfigProviderInterface $paymentTermConfigProvider)
    {
        $this->paymentTermConfigProvider = $paymentTermConfigProvider;
    }

    #[\Override]
    public function getPaymentConfigs()
    {
        if (null === $this->cachedConfigs) {
            $this->cachedConfigs = $this->paymentTermConfigProvider->getPaymentConfigs();
        }

        return $this->cachedConfigs;
    }

    #[\Override]
    public function getPaymentConfig($identifier)
    {
        if (false === $this->hasPaymentConfig($identifier)) {
            return null;
        }

        return $this->cachedConfigs[$identifier];
    }

    #[\Override]
    public function hasPaymentConfig($identifier)
    {
        $configs = $this->getPaymentConfigs();

        return array_key_exists($identifier, $configs);
    }
}
