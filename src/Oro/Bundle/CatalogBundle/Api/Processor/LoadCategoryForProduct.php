<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Computes a value of "category" association to Product entity.
 */
class LoadCategoryForProduct implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntitySerializer */
    private $entitySerializer;

    /**
     * @param DoctrineHelper   $doctrineHelper
     * @param EntitySerializer $entitySerializer
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntitySerializer $entitySerializer)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entitySerializer = $entitySerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data)) {
            return;
        }

        $categoryFieldName = $context->getResultFieldName('category');
        if (!$context->isFieldRequested($categoryFieldName, $data)) {
            return;
        }

        $productIdFieldName = $context->getResultFieldName('id');
        if (!$productIdFieldName || empty($data[$productIdFieldName])) {
            return;
        }

        $data[$categoryFieldName] = $this->loadCategoryData(
            $context->getConfig()->getField($categoryFieldName),
            $data[$productIdFieldName]
        );
        $context->setResult($data);
    }

    /**
     * @param EntityDefinitionFieldConfig $categoryField
     * @param int                         $productId
     *
     * @return array|null
     */
    private function loadCategoryData(EntityDefinitionFieldConfig $categoryField, int $productId): ?array
    {
        $data = $this->entitySerializer->serialize(
            $this->getCategoryQueryBuilder($categoryField->getTargetClass(), $productId),
            $categoryField->getTargetEntity()
        );

        if (empty($data)) {
            return null;
        }

        return $data[0];
    }

    /**
     * @param string $categoryEntityClass
     * @param int    $productId
     *
     * @return QueryBuilder
     */
    private function getCategoryQueryBuilder(string $categoryEntityClass, int $productId): QueryBuilder
    {
        $qb = $this->doctrineHelper
            ->getEntityRepositoryForClass($categoryEntityClass)
            ->createQueryBuilder('e')
            ->innerJoin('e.products', 'p')
            ->setMaxResults(1);
        $qb->where($qb->expr()->in('p', $productId));

        return $qb;
    }
}
