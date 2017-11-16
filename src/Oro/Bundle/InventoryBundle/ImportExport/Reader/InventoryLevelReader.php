<?php

namespace Oro\Bundle\InventoryBundle\ImportExport\Reader;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator\InventoryLevelIteratorStrategy;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;

class InventoryLevelReader extends EntityReader
{
    /** @var  string */
    protected $currentEntityName;

    /**
     * {@inheritdoc}
     */
    public function setSourceEntityName($entityName, Organization $organization = null, array $ids = [])
    {
        $this->currentEntityName = $entityName;
        parent::setSourceEntityName($entityName, $organization, $ids);
    }

    /**
     * @param ContextInterface $context
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        parent::initializeFromContext($context);
        $this->getSourceIterator()->setIterationStrategy(new InventoryLevelIteratorStrategy());
    }

    /**
     * {@inheritdoc}
     */
    protected function createSourceEntityQueryBuilder($entityName, Organization $organization = null, array $ids = [])
    {
        $qb = parent::createSourceEntityQueryBuilder($entityName, $organization);

        switch ($this->currentEntityName) {
            case Product::class:
                $qb->orderBy('o.sku', 'ASC');
                break;
            case InventoryLevel::class:
                $qb->orderBy('_product.sku', 'ASC');
                break;
            default:
                throw new \LogicException(sprintf("Invalid entity name provided: %s", $this->currentEntityName));
        }

        return $qb;
    }
}
