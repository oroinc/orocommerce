<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Event;

use Oro\Component\Website\WebsiteInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when sitemap dumping is completed.
 *
 * This event is triggered after the sitemap has been successfully generated and written to storage.
 * It carries information about the website and version of the completed sitemap, allowing listeners
 * to perform post-processing tasks such as notifying search engines or logging completion.
 */
class OnSitemapDumpFinishEvent extends Event
{
    public const EVENT_NAME = 'oro_seo.sitemap.event.on_sitemap_dump_finish';

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
