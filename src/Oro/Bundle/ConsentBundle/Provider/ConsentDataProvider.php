<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ConsentBundle\Builder\ConsentDataBuilder;
use Oro\Bundle\ConsentBundle\Filter\FrontendConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Filter\RequiredConsentFilter;
use Oro\Bundle\ConsentBundle\Helper\ConsentContextInitializeHelperInterface;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

/**
 * Data provider that helps to get DTO object ConsentData for certain website from config
 */
class ConsentDataProvider
{
    /**
     * @var EnabledConsentProvider
     */
    private $provider;

    /**
     * @var ConsentDataBuilder
     */
    private $consentDataBuilder;

    /**
     * @var ConsentContextInitializeHelperInterface
     */
    private $contextInitializeHelper;

    /**
     * @param EnabledConsentProvider $provider
     * @param ConsentDataBuilder $consentDataBuilder
     * @param ConsentContextInitializeHelperInterface $contextInitializeHelper
     */
    public function __construct(
        EnabledConsentProvider $provider,
        ConsentDataBuilder $consentDataBuilder,
        ConsentContextInitializeHelperInterface $contextInitializeHelper
    ) {
        $this->provider = $provider;
        $this->consentDataBuilder = $consentDataBuilder;
        $this->contextInitializeHelper = $contextInitializeHelper;
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return ConsentData[]
     */
    public function getAllConsentData(CustomerUser $customerUser = null)
    {
        $this->contextInitializeHelper->initialize($customerUser);

        return $this->getFilteredConsents([
            FrontendConsentContentNodeValidFilter::NAME
        ]);
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return ConsentData[]
     */
    public function getRequiredConsentData(CustomerUser $customerUser = null)
    {
        $this->contextInitializeHelper->initialize($customerUser);

        return $this->getFilteredConsents([
            FrontendConsentContentNodeValidFilter::NAME,
            RequiredConsentFilter::NAME
        ]);
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return ConsentData[]
     */
    public function getAcceptedConsentData(CustomerUser $customerUser = null)
    {
        $this->contextInitializeHelper->initialize($customerUser);

        /**
         * Accepted consents already contain resolved data (cmsPageId and URL calculated based on cmsPage slug),
         * so no need to validate it
         */
        $consents = $this->getFilteredConsents();

        $filteredConsents = array_filter($consents, function (ConsentData $consent) {
            return true === $consent->isAccepted();
        });

        return array_values($filteredConsents);
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return ConsentData[]
     */
    public function getNotAcceptedRequiredConsentData(CustomerUser $customerUser = null)
    {
        $this->contextInitializeHelper->initialize($customerUser);

        $consents = $this->getFilteredConsents([
            FrontendConsentContentNodeValidFilter::NAME,
            RequiredConsentFilter::NAME
        ]);

        $filteredConsents =  array_filter($consents, function (ConsentData $consent) {
            return false === $consent->isAccepted();
        });

        return array_values($filteredConsents);
    }

    /**
     * @param array $filters
     *
     * @return ConsentData[]
     */
    private function getFilteredConsents(array $filters = [])
    {
        $consents = $this->provider->getConsents($filters);
        return array_map([$this->consentDataBuilder, 'build'], $consents);
    }
}
