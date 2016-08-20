<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Oro\Bundle\RFPBundle\Entity\RequestStatus;

class LoadDefaultRequestStatus extends AbstractLoadRequestStatus
{
    /**
     * {@inheritDoc}
     */
    protected function getItems()
    {
        return [
            ['order' => 10, 'name' => RequestStatus::OPEN],
            ['order' => 20, 'name' => RequestStatus::CLOSED],
            ['order' => 30, 'name' => RequestStatus::DRAFT],
        ];
    }
}
