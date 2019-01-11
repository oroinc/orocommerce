<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Filter\AdminConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Filter\ConsentFilterInterface;
use Oro\Bundle\ConsentBundle\Filter\FrontendConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Filter\RequiredConsentFilter;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;

/**
 * Provides consents enabled in the config with additional filterable option
 */
class EnabledConsentProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ConsentConfigConverter
     */
    private $converter;

    /**
     * @var ConsentContextProviderInterface
     */
    private $contextProvider;

    /**
     * @var ConsentFilterInterface[]
     */
    private $filters;

    /**
     * @param ConfigManager $configManager
     * @param ConsentConfigConverter $converter
     * @param ConsentContextProviderInterface $contextProvider
     */
    public function __construct(
        ConfigManager $configManager,
        ConsentConfigConverter $converter,
        ConsentContextProviderInterface $contextProvider
    ) {
        $this->configManager = $configManager;
        $this->converter = $converter;
        $this->contextProvider = $contextProvider;
    }

    /**
     * @param ConsentFilterInterface $filter
     */
    public function addFilter(ConsentFilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * If no filters are passed, it will return all consents enabled in the configuration for customer user's website.
     *
     * @param array $enabledFilters
     * [
     *      'name of filter, by default (filter::NAME)',
     *      ...
     * ]
     * @param array $filterParams
     * [
     *     'key1' => 'value1',
     *      ...
     * ]
     * @return Consent[]
     */
    public function getConsents(array $enabledFilters = [], array $filterParams = [])
    {
        $consents = [];

        $consentConfigs = $this->getConsentConfigs();

        foreach ($consentConfigs as $consentConfig) {
            $consent = $consentConfig->getConsent();
            if ($this->filterConsent($consent, $enabledFilters, $filterParams)) {
                $consents[] = $consent;
            }
        }

        return $consents;
    }

    /**
     * @param ConsentAcceptance[] $consentAcceptances
     *
     * @return Consent[]
     */
    public function getUnacceptedRequiredConsents(array $consentAcceptances)
    {
        $checkedConsentIds = array_map(function (ConsentAcceptance $consentAcceptance) {
            return (int) $consentAcceptance->getConsent()->getId();
        }, $consentAcceptances);

        $requiredConsents = $this->getConsents([
            RequiredConsentFilter::NAME,
            AdminConsentContentNodeValidFilter::NAME,
            FrontendConsentContentNodeValidFilter::NAME
        ]);

        return array_filter($requiredConsents, function (Consent $consent) use ($checkedConsentIds) {
            return !in_array((int) $consent->getId(), $checkedConsentIds, true);
        });
    }

    /**
     * @param Consent $consent
     * @param array $enabledFilters
     * @param array $filterParams
     *
     * @return bool
     */
    private function filterConsent(Consent $consent, array $enabledFilters, array $filterParams)
    {
        foreach ($this->filters as $filter) {
            if (in_array($filter->getName(), $enabledFilters) &&
                !$filter->isConsentPassedFilter($consent, $filterParams)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return ConsentConfig[]
     */
    private function getConsentConfigs()
    {
        /**
         * If we can't resolve website, return empty result
         */
        $website = $this->contextProvider->getWebsite();
        if (!$website) {
            return [];
        }

        $consentConfigValue = $this->configManager->get(
            Configuration::getConfigKey(Configuration::ENABLED_CONSENTS),
            [],
            false,
            $website
        );

        return $this->converter->convertFromSaved($consentConfigValue);
    }
}
