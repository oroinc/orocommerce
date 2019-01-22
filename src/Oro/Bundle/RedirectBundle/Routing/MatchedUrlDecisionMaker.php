<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

/**
 * Checks if URL is storefront URL and it is not configured to be skipped on storefront.
 */
class MatchedUrlDecisionMaker
{
    /** @var FrontendHelper */
    private $frontendHelper;

    /** @var string[] */
    private $skippedUrlPatterns = [];

    /**
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * Skipped url pattern should start with slash.
     *
     * @param string $skippedUrlPattern
     */
    public function addSkippedUrlPattern($skippedUrlPattern)
    {
        $this->skippedUrlPatterns[] = $skippedUrlPattern;
    }

    /**
     * @param string $url
     * @return bool
     */
    public function matches($url)
    {
        return
            $this->frontendHelper->isFrontendUrl($url)
            && !$this->isSkippedUrl($url);
    }

    /**
     * @param string $url
     * @return bool
     */
    private function isSkippedUrl($url)
    {
        foreach ($this->skippedUrlPatterns as $pattern) {
            if (strpos($url, $pattern) === 0) {
                return true;
            }
        }

        return false;
    }
}
