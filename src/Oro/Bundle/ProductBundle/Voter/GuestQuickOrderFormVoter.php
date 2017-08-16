<?php

namespace Oro\Bundle\ProductBundle\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

class GuestQuickOrderFormVoter implements VoterInterface
{
    const GUEST_QUICK_ORDER_FORM_FEATURE = 'guest_quick_order_form';

    /** @var VoterInterface */
    private $configVoter;

    /** @var string */
    private $featureName;

    /**
     * @param VoterInterface $configVoter
     */
    public function __construct(VoterInterface $configVoter)
    {
        $this->configVoter  = $configVoter;
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
            return $this->configVoter->vote($this->featureName, $scopeIdentifier);
        }

        return VoterInterface::FEATURE_ABSTAIN;
    }
}
