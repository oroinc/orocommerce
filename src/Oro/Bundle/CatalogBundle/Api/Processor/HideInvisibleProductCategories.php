<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntityConfig;

/**
 * Sets invisible category to null for Product entity.
 */
class HideInvisibleProductCategories implements ProcessorInterface
{
    private QueryAclHelper $queryAclHelper;
    private DoctrineHelper $doctrineHelper;

    public function __construct(QueryAclHelper $queryAclHelper, DoctrineHelper $doctrineHelper)
    {
        $this->queryAclHelper = $queryAclHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
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
            $fieldConfig->getTargetEntity(),
            $context->getRequestType()
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
     * @return array [category id, ...]
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
     * @param RequestType  $requestType
     *
     * @return array [available category id => true, ...]
     */
    private function getAvailableCategoriesIds(
        array $categoryIds,
        EntityConfig $entityConfig,
        RequestType $requestType
    ): array {
        $qb = $this->doctrineHelper->createQueryBuilder(Category::class, 'e')
            ->select('e.id')
            ->where('e.id in (:ids)')
            ->setParameter('ids', $categoryIds);
        $query = $this->queryAclHelper->protectQuery($qb, $entityConfig, $requestType);
        $resultCategories = $query->getArrayResult();

        $result = [];
        foreach ($resultCategories as $categoryData) {
            $result[$categoryData['id']] = true;
        }

        return $result;
    }
}
