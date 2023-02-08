<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Filter\ConsentFilterCollection;
use Oro\Bundle\ConsentBundle\Filter\ConsentFilterInterface;

/**
 * Provides consents enabled in a config with additional filterable option.
 */
class EnabledConsentProvider
{
    private EnabledConsentConfigProviderInterface $enabledConsentConfigProvider;
    private ConsentFilterCollection $filters;

    public function __construct(
        EnabledConsentConfigProviderInterface $enabledConsentConfigProvider,
        ConsentFilterCollection $filters
    ) {
        $this->enabledConsentConfigProvider = $enabledConsentConfigProvider;
        $this->filters = $filters;
    }

    /**
     * Returns all consents enabled in a config that are passed by all given filters.
     * When filters are not specified, it will return all consents enabled in a config.
     *
     * @param string[] $enabledFilters
     * @param array    $filterParams
     *
     * @return Consent[]
     */
    public function getConsents(array $enabledFilters = [], array $filterParams = []): array
    {
        $consents = [];
        $consentConfigs = $this->enabledConsentConfigProvider->getConsentConfigs();
        foreach ($consentConfigs as $consentConfig) {
            $consent = $consentConfig->getConsent();
            if (null !== $consent && $this->isConsentPassedFilters($consent, $filterParams, $enabledFilters)) {
                $consents[] = $consent;
            }
        }

        return $consents;
    }

    /**
     * Returns all unaccepted required consents enabled in a config.
     *
     * @param ConsentAcceptance[] $consentAcceptances
     *
     * @return Consent[]
     */
    public function getUnacceptedRequiredConsents(array $consentAcceptances): array
    {
        $acceptedConsents = [];
        foreach ($consentAcceptances as $consentAcceptance) {
            $acceptedConsents[$consentAcceptance->getConsent()->getId()] = true;
        }

        $consents = [];
        $consentConfigs = $this->enabledConsentConfigProvider->getConsentConfigs();
        foreach ($consentConfigs as $consentConfig) {
            $consent = $consentConfig->getConsent();
            if (null !== $consent
                && !isset($acceptedConsents[$consent->getId()])
                && $this->isConsentPassedFilters($consent)
            ) {
                $consents[] = $consent;
            }
        }

        return $consents;
    }

    private function isConsentPassedFilters(
        Consent $consent,
        array $filterParams = [],
        ?array $enabledFilters = null
    ): bool {
        /** @var ConsentFilterInterface $filter */
        foreach ($this->filters as $filter) {
            if (null !== $enabledFilters && !\in_array($filter->getName(), $enabledFilters, true)) {
                continue;
            }

            if (!$filter->isConsentPassedFilter($consent, $filterParams)) {
                return false;
            }
        }

        return true;
    }
}
