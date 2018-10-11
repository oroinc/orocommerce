<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Allows to determine that guest link can be created in email template.
 */
class GuestQuoteAccessLinkProvider implements GuestQuoteAccessProviderInterface
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
        if ($quote->isExpired()) {
            return false;
        }

        if ($quote->getValidUntil() && $quote->getValidUntil() < new \DateTime('now', new \DateTimeZone('UTC'))) {
            return false;
        }

        if (!$this->featureChecker->isFeatureEnabled('guest_quote')) {
            return false;
        }

        return true;
    }
}
