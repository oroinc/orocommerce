<?php

namespace OroB2B\Bundle\AccountBundle\Audit;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class AuditDatagridListener
{
    /**
     * @param BuildBefore $event
     */
    public function onHistoryBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $config->offsetUnsetByPath('[source][query][select][3]');
    }

    /**
     * @param BuildBefore $event
     */
    public function onAuditBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $config->offsetUnsetByPath('[source][query][select][8]');
    }
}
