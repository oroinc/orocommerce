<?php

namespace Oro\Bundle\ProductBundle\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * Checks whether feature 'attachment_original_filenames' is disabled.
 */
class ProductOriginalFileNamesConfigurationVoter implements VoterInterface, FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private const FEATURE_NAME = 'product_original_filenames_configuration';

    /**
     * {@inheritDoc}
     */
    public function vote($feature, $scopeIdentifier = null): int
    {
        if ($feature !== self::FEATURE_NAME) {
            return self::FEATURE_ABSTAIN;
        }

        return $this->isFeaturesEnabled() ? self::FEATURE_DISABLED : self::FEATURE_ENABLED;
    }
}
