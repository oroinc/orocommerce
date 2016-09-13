<?php

namespace Oro\Bundle\WarehouseBundle\SystemConfig;

use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseConfig
{
    /** @var Warehouse */
    protected $warehouse;

    /** @var int|null */
    protected $priority;

    /**
     * @param Warehouse|null $warehouse
     * @param int|string|null $priority
     */
    public function __construct(Warehouse $warehouse = null, $priority = null)
    {
        $this->warehouse = $warehouse;
        $this->priority = $priority;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

    /**
     * @param Warehouse $warehouse
     * @return $this
     */
    public function setWarehouse($warehouse)
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int|string $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = (int)$priority;

        return $this;
    }
}
