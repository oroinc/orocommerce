<?php

namespace OroB2B\Bundle\WarehouseBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;

class BuildSingleWarehouseInventoryLevelQuery implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var CriteriaConnector */
    protected $criteriaConnector;

    /** @var  WarehouseCounter */
    protected $warehouseCounter;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param CriteriaConnector $criteriaConnector
     * @param WarehouseCounter $warehouseCounter
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        CriteriaConnector $criteriaConnector,
        WarehouseCounter $warehouseCounter
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->criteriaConnector = $criteriaConnector;
        $this->warehouseCounter = $warehouseCounter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var UpdateContext $context */

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $requestData = $context->getRequestData();
        if (!array_key_exists('sku', $requestData)) {
            // sku is required on request in order to identify a product
            return;
        }
        $sku = $requestData['sku'];
        unset($requestData['sku']);

        if ($this->warehouseCounter->areMoreWarehouses()) {
            if (!array_key_exists('warehouse', $requestData)) {
                // warehouse is required if there are more warehouses in the system
                return;
            }
            $warehouse = $requestData['warehouse'];
            unset($requestData['warehouse']);
        }

        $unit = $this->getUnit($requestData, $sku);
        unset($requestData['unit']);

        $queryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->criteriaConnector->applyCriteria($queryBuilder, $criteria);

        $queryBuilder
            ->leftJoin('e.product', 'product')
            ->leftJoin('e.productUnitPrecision', 'productPrecision')
            ->leftJoin('productPrecision.unit', 'unit')
            ->andWhere($queryBuilder->expr()->eq('product.sku', ':sku'))
            ->andWhere($queryBuilder->expr()->eq('unit.code', ':unit'))
            ->setParameter('sku', $sku)
            ->setParameter('unit', $unit);
        if (isset($warehouse)) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('e.warehouse', ':warehouse'))
                ->setParameter('warehouse', $warehouse);
        }

        $context->setQuery($queryBuilder);
        $context->setRequestData($requestData);
    }

    /**
     * @param array $requestData
     * @param string $sku
     * @return string
     */
    protected function getUnit(array $requestData, $sku)
    {
        return array_key_exists('unit', $requestData) ?
            $requestData['unit'] :
            $this->doctrineHelper->getEntityRepositoryForClass(Product::class)->getPrimaryUnitPrecisionCode($sku);
    }
}
