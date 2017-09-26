<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class WebsiteSearchMappingEvent extends Event
{
    const NAME = 'oro_website_search.event.website_search_mapping.configuration';

    /** @var array */
    protected $configuration = [];

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }
}
