<?php

namespace Oro\Bundle\ProductBundle\Voter;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class GuestQuickOrderFormVoter implements VoterInterface
{
    const GUEST_QUICK_ORDER_FORM_FEATURE = 'guest_quick_order';

    /** @var VoterInterface */
    private $configVoter;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var string */
    private $featureName;

    /**
     * @param VoterInterface $configVoter
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(VoterInterface $configVoter, TokenStorageInterface $tokenStorage)
    {
        $this->configVoter  = $configVoter;
        $this->tokenStorage = $tokenStorage;
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
        if ($feature === self::GUEST_QUICK_ORDER_FORM_FEATURE) {
            if (!$this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
                return VoterInterface::FEATURE_ENABLED;
            }

            return $this->configVoter->vote($this->featureName, $scopeIdentifier);
        }

        return VoterInterface::FEATURE_ABSTAIN;
    }
}
