<?php

namespace Oro\Bundle\ApruveBundle\Layout\Provider;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\Config\Provider\ApruveConfigProviderInterface;

class ApruveJsUriProvider implements ApruveJsUriProviderInterface
{
    const URI_TEST = 'oroapruve/js/lib/apruvejs-test';
    const URI_PROD = 'oroapruve/js/lib/apruvejs-prod';

    /**
     * @var ApruveConfigProviderInterface
     */
    protected $apruveConfigProvider;

    /**
     * @param ApruveConfigProviderInterface $apruveConfigProvider
     */
    public function __construct(ApruveConfigProviderInterface $apruveConfigProvider)
    {
        $this->apruveConfigProvider = $apruveConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri($paymentMethodIdentifier)
    {
        if ($this->getPaymentConfig($paymentMethodIdentifier)->isTestMode()) {
            return self::URI_TEST;
        }

        return self::URI_PROD;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($paymentMethodIdentifier)
    {
        return $this->apruveConfigProvider->hasPaymentConfig($paymentMethodIdentifier);
    }

    /**
     * @param string $identifier
     *
     * @return ApruveConfigInterface|null
     */
    protected function getPaymentConfig($identifier)
    {
        return $this->apruveConfigProvider->getPaymentConfig($identifier);
    }
}
