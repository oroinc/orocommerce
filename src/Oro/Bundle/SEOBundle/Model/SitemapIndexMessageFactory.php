<?php

namespace Oro\Bundle\SEOBundle\Model;

use Oro\Component\Website\WebsiteInterface;

class SitemapIndexMessageFactory extends AbstractSitemapMessageFactory
{
    /**
     * @param WebsiteInterface $website
     * @param int $version
     * @return array
     */
    public function createMessage(WebsiteInterface $website, $version)
    {
        return $this->getResolvedData(
            [
                self::WEBSITE_ID => $website->getId(),
                self::VERSION => $version,
            ]
        );
    }
}
