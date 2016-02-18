<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Data\ORM;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class LoadDefaultRequestStatus extends AbstractLoadDefaultRequestStatus
{
    /**
     * @var array
     */
    protected $items = [
        ['order' => 10, 'name' => RequestStatus::OPEN],
        ['order' => 20, 'name' => RequestStatus::CLOSED],
        ['order' => 30, 'name' => RequestStatus::DRAFT],
    ];
}
