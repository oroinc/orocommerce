<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class SelectDataFromSearchIndexEvent extends Event
{
    const EVENT_NAME = 'oro_website_search.select_index_data';

    /**
     * @var array
     */
    protected $selectedData;

    /**
     * @param array $selectedData
     */
    public function __construct(array $selectedData)
    {
        $this->selectedData = $selectedData;
    }

    /**
     * @return array
     */
    public function getSelectedData()
    {
        return $this->selectedData;
    }

    /**
     * @param array $selectedData
     * @return $this
     */
    public function setSelectedData($selectedData)
    {
        $this->selectedData = $selectedData;

        return $this;
    }
}
