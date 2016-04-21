<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class ShippingOriginProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Returns null if Warehouse uses ShippingOrigin from system configuration
     *
     * @param Warehouse $warehouse
     *
     * @return null|ShippingOriginWarehouse
     */
    public function getShippingOriginByWarehouse(Warehouse $warehouse)
    {
        /** @var EntityRepository $repo */
        $repo = $this->doctrineHelper
            ->getEntityManagerForClass('OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse')
            ->getRepository('OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse');

        /** @var ShippingOriginWarehouse $shippingOriginWarehouse */
        $shippingOriginWarehouse = $repo->findOneBy(['warehouse' => $warehouse]);

        if ($shippingOriginWarehouse) {
            return $shippingOriginWarehouse;
        }

        return null;
    }
}
