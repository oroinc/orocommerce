<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Allows to determine that quote is accessible by guest link on store front.
 */
class GuestQuoteAccessProvider implements GuestQuoteAccessProviderInterface
{
    /** @var FeatureChecker */
    private $featureChecker;

    /** @var WebsiteManager */
    private $websiteManager;

    public function __construct(FeatureChecker $featureChecker, WebsiteManager $websiteManager)
    {
        $this->featureChecker = $featureChecker;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(Quote $quote): bool
    {
        if (!$quote->isAvailableOnFrontend() || !$this->featureChecker->isFeatureEnabled('guest_quote')) {
            return false;
        }

        // Current website can be determined only for frontend requests.
        $website = $this->websiteManager->getCurrentWebsite();
        if (!$website) {
            return false;
        }

        return $quote->getWebsite() && $quote->getWebsite()->getId() === $website->getId();
    }
}
