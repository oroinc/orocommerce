<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Event;

use Oro\Component\Website\WebsiteInterface;
use Symfony\Contracts\EventDispatcher\Event;

class OnSitemapDumpFinishEvent extends Event
{
    const EVENT_NAME = 'oro_seo.sitemap.event.on_sitemap_dump_finish';

    /**
     * @var WebsiteInterface
     */
    private $website;

    /**
     * @var string
     */
    private $version;

    /**
     * @param WebsiteInterface $website
     * @param string $version
     */
    public function __construct(WebsiteInterface $website, $version)
    {
        $this->website = $website;
        $this->version = $version;
    }

    /**
     * @return WebsiteInterface
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
