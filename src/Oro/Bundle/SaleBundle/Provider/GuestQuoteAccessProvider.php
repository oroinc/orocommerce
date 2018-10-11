<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Allows to determine that quote is accessible by guest link.
 */
class GuestQuoteAccessProvider implements GuestQuoteAccessProviderInterface
{
    /** @var FeatureChecker */
    private $featureChecker;

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(Quote $quote): bool
    {
        return $quote->isAcceptable() && $this->featureChecker->isFeatureEnabled('guest_quote');
    }
}
