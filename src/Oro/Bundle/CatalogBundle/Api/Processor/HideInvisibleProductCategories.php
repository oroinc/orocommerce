<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\AclProtectedQueryFactory;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntityConfig;

/**
 * Sets invisible category to null for Product entity.
 */
class HideInvisibleProductCategories implements ProcessorInterface
{
    /** @var AclProtectedQueryFactory */
    private $queryFactory;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param AclProtectedQueryFactory $queryFactory
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(AclProtectedQueryFactory $queryFactory, DoctrineHelper $doctrineHelper)
    {
        $this->queryFactory = $queryFactory;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $categoryFieldName = $context->getResultFieldName('category');
        $fieldConfig = $context->getConfig()->getField($categoryFieldName);

        if (null === $fieldConfig || $fieldConfig->isExcluded()) {
            return;
        }

        $availableCategoriesIds = $this->getAvailableCategoriesIds(
            $this->getCategoriesIds($data, $categoryFieldName),
            $fieldConfig->getTargetEntity()
        );

        foreach ($data as $key => $item) {
            if (!isset($item[$categoryFieldName]['id'], $availableCategoriesIds[$item[$categoryFieldName]['id']])) {
                $data[$key][$categoryFieldName] = null;
            }
        }

        $context->setData($data);
    }

    /**
     * @param array  $data
     * @param string $categoryFieldName
     *
     * @return array [category1Id, catergory2Id, ...]
     */
    private function getCategoriesIds(array $data, string $categoryFieldName): array
    {
        $categories = [];
        foreach ($data as $item) {
            if (!empty($item[$categoryFieldName]['id'])) {
                $categories[] = $item[$categoryFieldName]['id'];
            }
        }

        return array_unique($categories);
    }

    /**
     * @param array        $categoryIds
     * @param EntityConfig $entityConfig
     *
     * @return array [availableCategoryId => true, ...]
     */
    private function getAvailableCategoriesIds(array $categoryIds, EntityConfig $entityConfig): array
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Category::class, 'e')
            ->select('e.id')
            ->where('e.id in (:ids)')
            ->setParameter('ids', $categoryIds);
        $query = $this->queryFactory->getQuery($qb, $entityConfig);
        $resultCategories = $query->getArrayResult();

        $result = [];
        foreach ($resultCategories as $categoryData) {
            $result[$categoryData['id']] = true;
        }

        return $result;
    }
}
