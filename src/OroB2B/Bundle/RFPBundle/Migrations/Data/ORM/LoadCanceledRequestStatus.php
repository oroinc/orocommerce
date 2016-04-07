<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Data\ORM;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class LoadCanceledRequestStatus extends AbstractLoadRequestStatus
{
    /**
     * {@inheritDoc}
     */
    protected function getItems()
    {
        return [
            ['order' => 15, 'name' => RequestStatus::CANCELED],
        ];
    }
}
