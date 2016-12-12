<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

class MatchedUrlDecisionMaker
{
    /**
     * @var bool
     */
    protected $installed;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var array
     */
    protected $skippedUrlPatterns = [];

    /**
     * @param FrontendHelper $frontendHelper
     * @param boolean $installed
     */
    public function __construct(
        FrontendHelper $frontendHelper,
        $installed
    ) {
        $this->frontendHelper = $frontendHelper;
        $this->installed = $installed;
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
        if (!$this->installed) {
            return false;
        }

        if (!$this->frontendHelper->isFrontendUrl($url)) {
            return false;
        }

        if ($this->isSkippedUrl($url)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $url
     * @return bool
     */
    protected function isSkippedUrl($url)
    {
        foreach ($this->skippedUrlPatterns as $pattern) {
            if (strpos($url, $pattern) === 0) {
                return true;
            }
        }

        return false;
    }
}
