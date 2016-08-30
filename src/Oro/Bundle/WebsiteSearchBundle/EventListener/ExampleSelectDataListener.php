<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Event\SelectDataFromSearchIndexEvent;

class ExampleSelectDataListener
{
    /**
     * @param SelectDataFromSearchIndexEvent $event
     */
    public function process(SelectDataFromSearchIndexEvent $event)
    {
        $fields   = $event->getSelectedData();

        // this is an example of how a listener could influence
        // the search index query to retrieve more data fields from it.
        $fields[] = 'defaultName';
        $fields[] = 'descriptions';

        $event->setSelectedData($fields);
    }
}
