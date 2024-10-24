<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Cached\Memory;

use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;

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
