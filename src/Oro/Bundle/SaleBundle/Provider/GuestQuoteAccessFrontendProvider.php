<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Allows to determine that quote is accessible by guest link on store front.
 */
class GuestQuoteAccessFrontendProvider implements GuestQuoteAccessProviderInterface
{
    /** @var GuestQuoteAccessProviderInterface */
    private $innerProvider;

    /** @var WebsiteManager */
    private $websiteManager;

    /**
     * @param GuestQuoteAccessProviderInterface $innerProvider
     * @param WebsiteManager $websiteManager
     */
    public function __construct(GuestQuoteAccessProviderInterface $innerProvider, WebsiteManager $websiteManager)
    {
        $this->innerProvider = $innerProvider;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(Quote $quote): bool
    {
        if (!$this->innerProvider->isGranted($quote)) {
            return false;
        }

        // Current website can be determined only for frontend requests.
        $website = $this->websiteManager->getCurrentWebsite();
        if ($website) {
            return $quote->getWebsite() && $quote->getWebsite()->getId() === $website->getId();
        }

        // Returns true for backoffice requests.
        return true;
    }
}
