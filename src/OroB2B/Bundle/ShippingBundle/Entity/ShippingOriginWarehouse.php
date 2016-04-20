<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

/**
 * ShippingOriginWarehouse
 * @ORM\Table("orob2b_shipping_orig_warehouse")
 * @ORM\Entity
 */
class ShippingOriginWarehouse extends ShippingOrigin
{
    /** @var  Warehouse */
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
}
