<?php

namespace Oro\Bundle\SEOBundle\Event;

use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\Event;

class UrlItemsProviderEndEvent extends Event
{
    const NAME = 'oro_seo.event.url_items_provider_end';

    /**
     * @var WebsiteInterface|null
     */
    protected $website;

    /**
     * @var int
     */
    protected $version;

    /**
     * @param int $version
     * @param WebsiteInterface|null $website
     */
    public function __construct($version, WebsiteInterface $website = null)
    {
        $this->version = $version;
        $this->website = $website;
    }

    /**
     * @return WebsiteInterface|null
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }
}
