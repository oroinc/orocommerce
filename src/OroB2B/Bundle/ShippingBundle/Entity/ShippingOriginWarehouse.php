<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

/**
 * @ORM\Table("orob2b_shipping_orig_warehouse")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class ShippingOriginWarehouse extends ShippingOrigin
{
    /**
     * @var Warehouse
     *
     * @ORM\OneToOne(targetEntity="OroB2B\Bundle\WarehouseBundle\Entity\Warehouse")
     * @ORM\JoinColumn(name="warehouse_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $warehouse;

    /**
     * @param Warehouse $warehouse
     *
     * @return ShippingOriginWarehouse
     */
    public function setWarehouse(Warehouse $warehouse)
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updated = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return boolean
     */
    public function isSystem()
    {
        return false;
    }
}
