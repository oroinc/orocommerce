<?php

namespace OroB2B\Bundle\RFPBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestFrontendDatagridListener
{
    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        foreach ($records as $record) {
            $record->addData(['isDraft' => $record->getValue('statusName') === RequestStatus::DRAFT]);
        }
    }
}
