<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Provides the WEBSITE_ID placeholder for website search field name resolution.
 *
 * This placeholder replaces the WEBSITE_ID token in search field names and index aliases with the ID
 * of the current website. This is fundamental to the multi-website search architecture, enabling separate index fields
 * for each website (e.g., "name_WEBSITE_ID" becomes "name_1" for website 1).
 * The default value is obtained from {@see WebsiteManager} and represents the currently active website
 * in the application context. Throws an exception if no current website is defined.
 */
class WebsiteIdPlaceholder extends AbstractPlaceholder
{
    const NAME = 'WEBSITE_ID';

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    public function __construct(WebsiteManager $websiteManager)
    {
        $this->websiteManager = $websiteManager;
    }

    #[\Override]
    public function getPlaceholder()
    {
        return self::NAME;
    }

    #[\Override]
    public function getDefaultValue()
    {
        $website = $this->websiteManager->getCurrentWebsite();

        if (!$website) {
            throw new \RuntimeException('Current website is not defined.');
        }

        return (string)$website->getId();
    }
}
