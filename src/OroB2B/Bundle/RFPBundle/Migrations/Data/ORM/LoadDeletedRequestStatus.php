<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Data\ORM;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class LoadDeletedRequestStatus extends AbstractLoadDefaultRequestStatus
{
    /**
     * @var array
     */
    protected $items = [
        ['order' => 100, 'name' => RequestStatus::DELETED],
    ];
}
