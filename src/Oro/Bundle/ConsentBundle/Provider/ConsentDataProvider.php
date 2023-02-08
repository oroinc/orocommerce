<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ConsentBundle\Builder\ConsentDataBuilder;
use Oro\Bundle\ConsentBundle\Filter\FrontendConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Filter\RequiredConsentFilter;
use Oro\Bundle\ConsentBundle\Model\ConsentData;

/**
 * Data provider that helps to get DTO object ConsentData for certain website from config
 */
class ConsentDataProvider
{
    private EnabledConsentProvider $provider;
    private ConsentDataBuilder $consentDataBuilder;

    public function __construct(
        EnabledConsentProvider $provider,
        ConsentDataBuilder $consentDataBuilder
    ) {
        $this->provider = $provider;
        $this->consentDataBuilder = $consentDataBuilder;
    }

    /**
     * @return ConsentData[]
     */
    public function getAllConsentData(): array
    {
        return $this->getFilteredConsents([
            FrontendConsentContentNodeValidFilter::NAME
        ]);
    }

    /**
     * @return ConsentData[]
     */
    public function getNotAcceptedRequiredConsentData(): array
    {
        $consents = $this->getRequiredConsentData();

        $filteredConsents =  array_filter($consents, function (ConsentData $consent) {
            return false === $consent->isAccepted();
        });

        return array_values($filteredConsents);
    }

    /**
     * @return ConsentData[]
     */
    public function getRequiredConsentData(): array
    {
        $consents = $this->getFilteredConsents([
            FrontendConsentContentNodeValidFilter::NAME,
            RequiredConsentFilter::NAME
        ]);

        return array_values($consents);
    }

    /**
     * @param string[] $filters
     *
     * @return ConsentData[]
     */
    private function getFilteredConsents(array $filters = []): array
    {
        $consents = $this->provider->getConsents($filters);

        return array_map([$this->consentDataBuilder, 'build'], $consents);
    }
}
