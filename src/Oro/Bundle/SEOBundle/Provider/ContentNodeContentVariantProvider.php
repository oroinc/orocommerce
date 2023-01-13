<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

/**
 * Provides localized information about metadata fields, for Content variant.
 */
class ContentNodeContentVariantProvider implements ContentVariantProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSupportedClass($className)
    {
        return $className === Product::class;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyNodeQueryBuilderByEntities(QueryBuilder $queryBuilder, $entityClass, array $entities)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getValues(ContentNodeInterface $node)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalizedValues(ContentNodeInterface $node)
    {
        return [
            'metaTitles' => $node->getMetaTitles(),
            'metaDescriptions' => $node->getMetaDescriptions(),
            'metaKeywords' => $node->getMetaKeywords(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordId(array $item)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordSortOrder(array $item)
    {
        return null;
    }
}
