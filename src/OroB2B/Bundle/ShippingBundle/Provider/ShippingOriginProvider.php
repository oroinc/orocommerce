<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use Oro\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;

use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class ShippingOriginProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ShippingOriginModelFactory */
    protected $shippingOriginModelFactory;

    /** @var EntityRepository */
    protected $warehouseShippingOriginRepository;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     * @param ShippingOriginModelFactory $shippingOriginModelFactory
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        ShippingOriginModelFactory $shippingOriginModelFactory
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->shippingOriginModelFactory = $shippingOriginModelFactory;
    }

    /**
     * @param Warehouse $warehouse
     *
     * @return ShippingOrigin
     */
    public function getShippingOriginByWarehouse(Warehouse $warehouse)
    {
        $repo = $this->getWarehouseShippingOriginRepository();

        /** @var ShippingOriginWarehouse $shippingOriginWarehouse */
        $shippingOriginWarehouse = $repo->findOneBy(['warehouse' => $warehouse]);

        if (!empty($shippingOriginWarehouse)) {
            return $shippingOriginWarehouse;
        }

        return $this->getSystemShippingOrigin();
    }

    /**
     * @return ShippingOrigin
     */
    public function getSystemShippingOrigin()
    {
        $configData = $this->configManager->get('orob2b_shipping.shipping_origin') ?: [];

        return $this->shippingOriginModelFactory->create($configData);
    }

    /**
     * @return EntityRepository
     */
    protected function getWarehouseShippingOriginRepository()
    {
        $repo = $this->doctrineHelper
            ->getEntityManagerForClass('Oro\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse')
            ->getRepository('Oro\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse');

        return $repo;
    }
}
