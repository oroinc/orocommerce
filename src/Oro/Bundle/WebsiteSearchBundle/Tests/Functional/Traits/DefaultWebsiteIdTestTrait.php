<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits;

use Oro\Bundle\WebsiteBundle\Entity\Website;

trait DefaultWebsiteIdTestTrait
{
    /**
     * @return int
     */
    protected function getDefaultWebsiteId()
    {
        return $this->getDefaultWebsite()->getId();
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        return $this
            ->getContainer()
            ->get('oro_website.manager')
            ->getDefaultWebsite();
    }
}
