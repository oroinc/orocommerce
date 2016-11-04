<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits;

trait DefaultWebsiteIdTestTrait
{
    /**
     * @return int
     */
    protected function getDefaultWebsiteId()
    {
        return $this
            ->getContainer()
            ->get('oro_website.manager')
            ->getDefaultWebsite()
            ->getId();
    }
}
