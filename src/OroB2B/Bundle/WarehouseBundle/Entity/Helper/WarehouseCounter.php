<?php

namespace OroB2B\Bundle\WarehouseBundle\Entity\Helper;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseCounter
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * WarehouseCounter constructor.
     *
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return bool
     */
    public function areMoreWarehouses()
    {
        return $this->doctrineHelper->getEntityRepository(Warehouse::class)->countWarehouses() > 1;
    }
}
