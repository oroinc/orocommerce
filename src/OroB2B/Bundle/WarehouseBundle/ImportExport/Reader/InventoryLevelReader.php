<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Reader;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ImportExportBundle\Reader\EntityReader;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class InventoryLevelReader extends EntityReader
{
    /** @var  string */
    protected $currentEntityName;

    /**
     * {@inheritdoc}
     */
    public function setSourceEntityName($entityName, Organization $organization = null)
    {
        $this->currentEntityName = $entityName;
        parent::setSourceEntityName($entityName, $organization);
    }

    /**
     * {@inheritdoc}
     */
    public function setSourceQueryBuilder(QueryBuilder $queryBuilder)
    {
        switch ($this->currentEntityName) {
            case Product::class:
                $queryBuilder->orderBy('o.sku', 'ASC');
                break;
            case WarehouseInventoryLevel::class:
                $queryBuilder->orderBy('_product.sku', 'ASC');
                break;
            default:
                throw new \LogicException(sprintf("Invalid entity name provided: %s", $this->currentEntityName));
        }

        parent::setSourceQueryBuilder($queryBuilder);
    }
}
