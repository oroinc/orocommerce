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
    private $skippedUrlPatterns;

    /** @var array */
    private $checkedUrls = [];

    /**
     * @param string[]       $skippedUrlPatterns
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(array $skippedUrlPatterns, FrontendHelper $frontendHelper)
    {
        $this->skippedUrlPatterns = $skippedUrlPatterns;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * Registers an additional pattern for skipped urls.
     *
     * @param string $skippedUrlPattern The pattern; should start with the slash, e.g. "/test"
     */
    public function addSkippedUrlPattern(string $skippedUrlPattern): void
    {
        $this->skippedUrlPatterns[] = $skippedUrlPattern;
        if ($this->checkedUrls) {
            $this->checkedUrls = [];
        }
    }

    public function matches(string $url): bool
    {
        return
            $this->frontendHelper->isFrontendUrl($url)
            && !$this->isSkippedUrl($url);
    }

    private function isSkippedUrl(string $url): bool
    {
        $result = $this->checkedUrls[$url] ?? null;
        if (null === $result) {
            $result = false;
            foreach ($this->skippedUrlPatterns as $pattern) {
                if (strpos($url, $pattern) === 0) {
                    $result = true;
                    break;
                }
            }
            $this->checkedUrls[$url] = $result;
        }

        return $result;
    }
}
