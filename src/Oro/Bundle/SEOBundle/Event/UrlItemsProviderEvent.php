<?php

namespace Oro\Bundle\SEOBundle\Event;

use Oro\Component\Website\WebsiteInterface;
use Symfony\Contracts\EventDispatcher\Event;

class UrlItemsProviderEvent extends Event
{
    public const ON_END = 'oro_seo.event.url_items_provider_end';
    public const ON_START = 'oro_seo.event.url_items_provider_start';

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
    public function __construct($version, ?WebsiteInterface $website = null)
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
