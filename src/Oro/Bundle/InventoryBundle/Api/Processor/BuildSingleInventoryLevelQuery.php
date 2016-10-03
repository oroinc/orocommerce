<?php

namespace Oro\Bundle\InventoryBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class BuildSingleInventoryLevelQuery implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var CriteriaConnector */
    protected $criteriaConnector;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param CriteriaConnector $criteriaConnector
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        CriteriaConnector $criteriaConnector
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->criteriaConnector = $criteriaConnector;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof UpdateContext) {
            return;
        }

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

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

        $unit = $this->getUnit($requestData, $sku);
        if (is_null($unit)) {
            // unit is required if there no unit found we stop process
            return;
        }
        
        unset($requestData['unit']);

        $queryBuilder = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->criteriaConnector->applyCriteria($queryBuilder, $criteria);

        $queryBuilder
            ->leftJoin('e.product', 'product')
            ->leftJoin('e.productUnitPrecision', 'productPrecision')
            ->andWhere($queryBuilder->expr()->eq('product.sku', ':sku'))
            ->andWhere($queryBuilder->expr()->eq('IDENTITY(productPrecision.unit)', ':unit'))
            ->setParameter('sku', $sku)
            ->setParameter('unit', $unit);

        $context->setQuery($queryBuilder);
        $context->setRequestData($requestData);
    }

    /**
     * @param array $requestData
     * @param string $sku
     * @return string|null
     */
    protected function getUnit(array $requestData, $sku)
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);

        return array_key_exists('unit', $requestData) ?
            $requestData['unit'] :
            $productRepository->getPrimaryUnitPrecisionCode($sku);
    }
}
