<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;

/**
 * Provides segments that attached to some content variants and uses relations in filter conditions.
 */
class SegmentWithRelationsProvider
{
    /**
     * @var ContentVariantSegmentProvider
     */
    private $contentVariantSegmentProvider;

    /**
     * @param ContentVariantSegmentProvider $contentVariantSegmentProvider
     */
    public function __construct(ContentVariantSegmentProvider $contentVariantSegmentProvider)
    {
        $this->contentVariantSegmentProvider = $contentVariantSegmentProvider;
    }

    /**
     * @return \Generator
     */
    public function getSegmentsWithRelations()
    {
        $joinIdentifierHelper = new JoinIdentifierHelper(Product::class);
        $segmentIterator = $this->contentVariantSegmentProvider->getContentVariantSegments();
        foreach ($segmentIterator as $segment) {
            $definition = json_decode($segment->getDefinition(), JSON_OBJECT_AS_ARRAY);
            if (isset($definition['filters']) && is_array($definition['filters'])) {
                foreach ($definition['filters'] as $filter) {
                    $joinParts = $joinIdentifierHelper->explodeJoinIdentifier($filter['columnName']);
                    if (count($joinParts) > 1) {
                        yield $segment;
                        break;
                    }
                }
            }
        }
    }
}
