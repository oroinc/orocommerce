<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\WarehouseProBundle\Entity\Warehouse;

/**
 * @ORM\Table("oro_shipping_orig_warehouse")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class ShippingOriginWarehouse extends ShippingOrigin
{
    /**
     * @var Warehouse
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\WarehouseProBundle\Entity\Warehouse")
     * @ORM\JoinColumn(name="warehouse_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $warehouse;

    /** @var bool */
    protected $system = false;

    /**
     * @param Warehouse $warehouse
     *
     * @return $this
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
     * @ORM\PostLoad()
     */
    public function postLoad()
    {
        $this->data = new \ArrayObject(
            [
                'country' => $this->country,
                'region' => $this->region,
                'region_text' => $this->regionText,
                'postalCode' => $this->postalCode,
                'city' => $this->city,
                'street' => $this->street,
                'street2' => $this->street2
            ]
        );
    }

    /**
     * @param ShippingOrigin $shippingOrigin
     * @return $this
     */
    public function import(ShippingOrigin $shippingOrigin)
    {
        $this->setCountry($shippingOrigin->getCountry())
            ->setRegion($shippingOrigin->getRegion())
            ->setRegionText($shippingOrigin->getRegionText())
            ->setPostalCode($shippingOrigin->getPostalCode())
            ->setCity($shippingOrigin->getCity())
            ->setStreet($shippingOrigin->getStreet())
            ->setStreet2($shippingOrigin->getStreet2());

        return $this;
    }
}
