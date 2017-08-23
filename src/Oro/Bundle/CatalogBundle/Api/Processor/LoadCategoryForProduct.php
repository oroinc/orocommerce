<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

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
        if (!is_array($data) || array_key_exists('category', $data) || !array_key_exists('id', $data)) {
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            return;
        }

        $categoryField = $config->findField('category');
        if (null === $categoryField || $categoryField->isExcluded()) {
            return;
        }

        $data['category'] = $this->loadCategoryData($categoryField, $data['id']);
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
