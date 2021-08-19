<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

/**
 * Checks if URL is storefront URL and it is not configured to be skipped on storefront.
 */
class MatchedUrlDecisionMaker
{
    private FrontendHelper $frontendHelper;
    /** @var string[] */
    private array $skippedUrlPatterns;
    /** @var bool[] [pathinfo => check result, ...] */
    private array $checkedUrls = [];

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

    public function matches(string $pathinfo): bool
    {
        return
            $this->frontendHelper->isFrontendUrl($pathinfo)
            && !$this->isSkippedUrl($pathinfo);
    }

    private function isSkippedUrl(string $pathinfo): bool
    {
        $result = $this->checkedUrls[$pathinfo] ?? null;
        if (null === $result) {
            $result = false;
            foreach ($this->skippedUrlPatterns as $pattern) {
                if (str_starts_with($pathinfo, $pattern)) {
                    $result = true;
                    break;
                }
            }
            $this->checkedUrls[$pathinfo] = $result;
        }

        return $result;
    }
}
