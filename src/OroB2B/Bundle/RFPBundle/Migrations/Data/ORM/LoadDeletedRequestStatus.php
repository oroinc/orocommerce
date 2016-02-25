<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Data\ORM;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

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
