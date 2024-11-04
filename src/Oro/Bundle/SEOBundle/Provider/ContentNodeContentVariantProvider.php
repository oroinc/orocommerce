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
    #[\Override]
    public function isSupportedClass($className)
    {
        return $className === Product::class;
    }

    #[\Override]
    public function modifyNodeQueryBuilderByEntities(QueryBuilder $queryBuilder, $entityClass, array $entities)
    {
    }

    #[\Override]
    public function getValues(ContentNodeInterface $node)
    {
        return [];
    }

    #[\Override]
    public function getLocalizedValues(ContentNodeInterface $node)
    {
        return [
            'metaTitles' => $node->getMetaTitles(),
            'metaDescriptions' => $node->getMetaDescriptions(),
            'metaKeywords' => $node->getMetaKeywords(),
        ];
    }

    #[\Override]
    public function getRecordId(array $item)
    {
        return null;
    }

    #[\Override]
    public function getRecordSortOrder(array $item)
    {
        return null;
    }
}
