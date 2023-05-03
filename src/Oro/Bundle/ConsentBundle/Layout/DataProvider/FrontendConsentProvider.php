<?php

namespace Oro\Bundle\ConsentBundle\Layout\DataProvider;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Layout\DTO\RequiredConsentData;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Layout data provider that helps to get DTO object ConsentData for certain website from config
 */
class FrontendConsentProvider implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private const CUSTOMER_CONSENTS_STEP = 'customer_consents';

    private ConsentDataProvider $provider;
    private TokenStorageInterface $tokenStorage;

    public function __construct(ConsentDataProvider $provider, TokenStorageInterface $tokenStorage)
    {
        $this->provider = $provider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return ConsentData[]
     */
    public function getAllConsentData(): array
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        return $this->provider->getAllConsentData();
    }

    /**
     * @param ConsentAcceptance[] $consentAcceptances
     */
    public function getAcceptedRequiredConsentData(array $consentAcceptances = []): RequiredConsentData
    {
        if (!$this->isFeaturesEnabled()) {
            return new RequiredConsentData();
        }

        $requiredConsentData = $this->provider->getRequiredConsentData();
        $filteredConsentData = $this->formatConsentData($requiredConsentData);
        $this->updateAcceptedStatus($filteredConsentData, $consentAcceptances);

        $requiredAcceptedConsentData = array_filter(
            $filteredConsentData,
            static fn ($consentData) => $consentData->isAccepted()
        );

        return new RequiredConsentData(array_values($requiredAcceptedConsentData), count($requiredConsentData));
    }

    /**
     * Provides ConsentData models with applied accepted flag according to ConsentAcceptance entities.
     *
     * @param ConsentAcceptance[] $consentAcceptances
     *
     * @return ConsentData[]
     */
    public function getConsentData(array $consentAcceptances = []): array
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        $filteredConsentData = $this->formatConsentData($this->provider->getNotAcceptedRequiredConsentData());
        $this->updateAcceptedStatus($filteredConsentData, $consentAcceptances);

        return array_values($filteredConsentData);
    }

    /**
     * @param ConsentAcceptance[] $consentAcceptances
     *
     * @return ConsentData[]
     */
    public function getNotAcceptedRequiredConsentData(array $consentAcceptances = []): array
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        $filteredConsentData = $this->formatConsentData($this->provider->getNotAcceptedRequiredConsentData());

        foreach ($consentAcceptances as $acceptance) {
            $key = $this->getKey(
                $acceptance->getConsent()->getId(),
                $acceptance->getLandingPage()?->getId()
            );
            if (array_key_exists($key, $filteredConsentData)) {
                unset($filteredConsentData[$key]);
            }
        }

        return array_values($filteredConsentData);
    }

    public function isCustomerUserCurrentlyLoggedIn(CustomerUser $customerUser): bool
    {
        return $customerUser === $this->getCustomerUser();
    }

    public function getExcludedSteps(array $excludedSteps = [], bool $hideConsentsStep = true): array
    {
        if (!$this->isFeaturesEnabled() || ($hideConsentsStep && !$this->getNotAcceptedRequiredConsentData())) {
            $excludedSteps[] = self::CUSTOMER_CONSENTS_STEP;
        }

        return $excludedSteps;
    }

    /**
     * @return CustomerUser|null
     */
    private function getCustomerUser()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && ($user = $token->getUser()) instanceof CustomerUser) {
            return $user;
        }

        return null;
    }

    /**
     * @param string|int $consentId
     * @param string|int|null $landingPageId
     *
     * @return string
     */
    private function getKey($consentId, $landingPageId): string
    {
        $testArray = [$consentId, $landingPageId];

        return implode('_', $testArray);
    }

    /**
     * @param ConsentData[] $consentsData
     * @return array
     *  [
     *      'consentId_landingPageId' => ConsentData $consentData,
     *      // ...
     *  ]
     */
    private function formatConsentData(array $consentsData): array
    {
        $formattedConsentData = [];
        foreach ($consentsData as $consentData) {
            $cmsPageData = $consentData->getCmsPageData();
            $key = $this->getKey($consentData->getId(), $cmsPageData?->getId());
            $formattedConsentData[$key] = $consentData;
        }

        return $formattedConsentData;
    }

    private function updateAcceptedStatus(array &$consentData, array $consentAcceptances): void
    {
        foreach ($consentAcceptances as $acceptance) {
            $key = $this->getKey(
                $acceptance->getConsent()->getId(),
                $acceptance->getLandingPage()?->getId()
            );
            if (array_key_exists($key, $consentData)) {
                $consentData[$key]->setAccepted(true);
            }
        }
    }
}
