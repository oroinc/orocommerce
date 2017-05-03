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
     * {@inheritDoc}
     */
    public function getUri($paymentMethodIdentifier)
    {
        if (!$this->apruveConfigProvider->hasPaymentConfig($paymentMethodIdentifier)) {
            return null;
        }

        if ($this->getPaymentConfig($paymentMethodIdentifier)->isTestMode()) {
            return self::URI_TEST;
        }

        return self::URI_PROD;
    }

    /**
     * @param string $identifier
     *
     * @return ApruveConfigInterface|null
     */
    private function getPaymentConfig($identifier)
    {
        return $this->apruveConfigProvider->getPaymentConfig($identifier);
    }
}
