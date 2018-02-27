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
 * Adds a value of "category" association to Product entity.
 */
class LoadCategoryForProduct implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntitySerializer */
    protected $entitySerializer;

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

        $config = $context->getConfig();

        $categoryFieldName = $config->findFieldNameByPropertyPath('category');
        if (!$categoryFieldName
            || $config->getField($categoryFieldName)->isExcluded()
            || array_key_exists($categoryFieldName, $data)
        ) {
            // the category field is undefined, excluded or already added
            return;
        }

        $productIdFieldName = $config->findFieldNameByPropertyPath('id');
        if (!$productIdFieldName || empty($data[$productIdFieldName])) {
            // the product id field is undefined or its value is unknown
            return;
        }

        $data[$categoryFieldName] = $this->loadCategoryData(
            $config->getField($categoryFieldName),
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
    protected function loadCategoryData(EntityDefinitionFieldConfig $categoryField, $productId)
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
    protected function getCategoryQueryBuilder($categoryEntityClass, $productId)
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
