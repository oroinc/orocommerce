<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Computes a value of "categoryPath" field for Category entity.
 */
class ComputeCategoryPath implements ProcessorInterface
{
    private const CATEGORY_PATH_FIELD     = 'categoryPath';
    private const MATERIALIZED_PATH_FIELD = 'materializedPath';

    /** @var EntitySerializer */
    private $entitySerializer;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param EntitySerializer $entitySerializer
     * @param DoctrineHelper   $doctrineHelper
     */
    public function __construct(EntitySerializer $entitySerializer, DoctrineHelper $doctrineHelper)
    {
        $this->entitySerializer = $entitySerializer;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data) || empty($data)) {
            return;
        }

        if (!$context->isFieldRequestedForCollection(self::CATEGORY_PATH_FIELD, $data)) {
            return;
        }

        $categoryParents = [];
        foreach ($data as $key => $item) {
            $parentIds = explode('_', $item[self::MATERIALIZED_PATH_FIELD]);
            // remove the last item because it is the same as current category id.
            array_pop($parentIds);
            $categoryParents[$item['id']] = $parentIds;
        }

        $categories = $this->loadCategoriesData(
            array_unique(array_merge(...array_values($categoryParents))),
            $context->getConfig()->getField(self::CATEGORY_PATH_FIELD)->getTargetEntity()
        );

        foreach ($data as $key => $item) {
            $resultCategories = [];
            $id = $item['id'];
            $parentIds = $categoryParents[$id];
            foreach ($parentIds as $categoryId) {
                if (!empty($categories[$categoryId])) {
                    $resultCategories[] = $categories[$categoryId];
                }
            }
            $data[$key][self::CATEGORY_PATH_FIELD] = $resultCategories;
        }

        $context->setResult($data);
    }

    /**
     * @param int[]                  $categoriesIds
     * @param EntityDefinitionConfig $config
     *
     * @return array [categoryId => [category_data], ...]
     */
    private function loadCategoriesData(array $categoriesIds, EntityDefinitionConfig $config): array
    {
        $qb = $this->doctrineHelper
            ->createQueryBuilder(Category::class, 'c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $categoriesIds);

        $categories = $this->entitySerializer->serialize($qb, $config);

        $result = [];
        foreach ($categories as $category) {
            $result[$category['id']] = $category;
        }

        return $result;
    }
}
