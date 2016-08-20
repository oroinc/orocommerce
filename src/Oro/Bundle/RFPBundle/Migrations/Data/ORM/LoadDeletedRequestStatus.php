<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Oro\Bundle\RFPBundle\Entity\RequestStatus;

class LoadDeletedRequestStatus extends AbstractLoadRequestStatus
{
    /**
     * {@inheritDoc}
     */
    protected function getItems()
    {
        return [
            ['order' => 100, 'name' => RequestStatus::DELETED],
        ];
    }
}
