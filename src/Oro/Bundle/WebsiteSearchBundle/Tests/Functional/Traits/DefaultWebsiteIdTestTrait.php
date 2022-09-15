<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits;

use Oro\Bundle\WebsiteBundle\Entity\Website;

trait DefaultWebsiteIdTestTrait
{
    protected static function getDefaultWebsiteId(): int
    {
        return static::getDefaultWebsite()->getId();
    }

    protected static function getDefaultWebsite(): Website
    {
        return self::getContainer()
            ->get('oro_website.manager')
            ->getDefaultWebsite();
    }
}
