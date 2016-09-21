<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

interface CacheBuilderInterface
{
    /**
     * @param VisibilityInterface $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings);

    /**
     * @param VisibilityInterface $visibilitySettings
     * @return mixed
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings);

    /**
     * @param Website|null $website
     * @return mixed
     */
    public function buildCache(Website $website = null);
}
