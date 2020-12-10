<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event allow to change search mapping config before the first usage of this mapping config for wesite search.
 */
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
