<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\WebsiteSearchTermBundle\Formatter\SearchTermPhrasesFormatter;

/**
 * Adds array of phrases to search terms grid for the current result set
 */
class AddPhrasesViewDataDatagridListener
{
    public function __construct(private SearchTermPhrasesFormatter $phrasesFormatter)
    {
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        foreach ($event->getRecords() as $result) {
            $result->setValue(
                'phrasesViewData',
                $this->phrasesFormatter->formatPhrasesToArray($result->getValue('phrases'))
            );
        }
    }
}
