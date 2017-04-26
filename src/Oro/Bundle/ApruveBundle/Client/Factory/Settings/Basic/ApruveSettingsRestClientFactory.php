<?php

namespace Oro\Bundle\ApruveBundle\Client\Factory\Settings\Basic;

use Oro\Bundle\ApruveBundle\Client\Factory\ApruveRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Factory\Settings\ApruveSettingsRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class ApruveSettingsRestClientFactory implements ApruveSettingsRestClientFactoryInterface
{
    /**
     * @var RestClientFactoryInterface
     */
    private $restClientFactory;

    /**
     * @var SymmetricCrypterInterface
     */
    private $symmetricCrypter;

    /**
     * @param ApruveRestClientFactoryInterface $restClientFactory
     * @param SymmetricCrypterInterface        $symmetricCrypter
     */
    public function __construct(
        ApruveRestClientFactoryInterface $restClientFactory,
        SymmetricCrypterInterface $symmetricCrypter
    ) {
        $this->restClientFactory = $restClientFactory;
        $this->symmetricCrypter = $symmetricCrypter;
    }

    /**
     * {@inheritDoc}
     */
    public function create(ApruveSettings $apruveSettings)
    {
        $apiKey = $this->symmetricCrypter->decryptData($apruveSettings->getApruveApiKey());

        $isTestMode = $apruveSettings->getApruveTestMode();

        return $this->restClientFactory->create($apiKey, $isTestMode);
    }
}
