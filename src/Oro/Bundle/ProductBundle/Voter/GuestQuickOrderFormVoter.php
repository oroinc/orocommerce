<?php

namespace Oro\Bundle\ProductBundle\Voter;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Check that dependent features are enabled for anonymous users, for logged-in users vote enabled.
 */
class GuestQuickOrderFormVoter implements VoterInterface
{
    /** @var VoterInterface */
    private $configVoter;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var string */
    private $featureName;

    /** @var string */
    private $baseFeatureName;

    /**
     * @param VoterInterface $configVoter
     * @param TokenStorageInterface $tokenStorage
     * @param string $baseFeature
     */
    public function __construct(VoterInterface $configVoter, TokenStorageInterface $tokenStorage, $baseFeature)
    {
        $this->configVoter  = $configVoter;
        $this->tokenStorage = $tokenStorage;
        $this->baseFeatureName = $baseFeature;
    }

    /**
     * @param string $featureName
     */
    public function setFeatureName($featureName)
    {
        $this->featureName = $featureName;
    }

    /**
     * {@inheritDoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        if ($feature === $this->baseFeatureName) {
            if (!$this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
                return VoterInterface::FEATURE_ENABLED;
            }

            return $this->configVoter->vote($this->featureName, $scopeIdentifier);
        }

        return VoterInterface::FEATURE_ABSTAIN;
    }
}
