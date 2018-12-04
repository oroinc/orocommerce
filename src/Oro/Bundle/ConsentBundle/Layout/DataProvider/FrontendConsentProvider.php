<?php

namespace Oro\Bundle\ConsentBundle\Layout\DataProvider;

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
    const CUSTOMER_CONSENTS_STEP = 'customer_consents';

    use FeatureCheckerHolderTrait;

    /**
     * @var ConsentDataProvider
     */
    private $provider;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param ConsentDataProvider $provider
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ConsentDataProvider $provider, TokenStorageInterface $tokenStorage)
    {
        $this->provider = $provider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return ConsentData[]
     */
    public function getAllConsentData(CustomerUser $customerUser = null)
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        return $this->provider->getAllConsentData($customerUser);
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return ConsentData[]
     */
    public function getRequiredConsentData(CustomerUser $customerUser = null)
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        return $this->provider->getRequiredConsentData($customerUser);
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return ConsentData[]
     */
    public function getAcceptedConsentData(CustomerUser $customerUser = null)
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        return $this->provider->getAcceptedConsentData($customerUser);
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return ConsentData[]
     */
    public function getNotAcceptedRequiredConsentData(CustomerUser $customerUser = null)
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        return $this->provider->getNotAcceptedRequiredConsentData($customerUser);
    }

    /**
     * @param CustomerUser $customerUser
     *
     * @return bool
     */
    public function isCustomerUserCurrentlyLoggedIn(CustomerUser $customerUser)
    {
        return $customerUser === $this->getCustomerUser();
    }

    /**
     * @param array $excludedSteps
     *
     * @return array
     */
    public function getExcludedSteps(array $excludedSteps = [])
    {
        if (!$this->isFeaturesEnabled()) {
            $excludedSteps[] = self::CUSTOMER_CONSENTS_STEP;
        }

        return $excludedSteps;
    }

    /**
     * @param int $actualStep
     *
     * @return int
     */
    public function getStepOrder($actualStep)
    {
        if (!$this->isFeaturesEnabled()) {
            --$actualStep;
        }

        return $actualStep;
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
}
