<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Oro\Bundle\RFPBundle\Entity\RequestStatus;

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
