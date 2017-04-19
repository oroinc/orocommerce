<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;

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
     * @return \Generator|Segment[]
     */
    public function getSegmentsWithRelations()
    {
        $joinIdentifierHelper = new JoinIdentifierHelper(Product::class);
        $segmentIterator = $this->contentVariantSegmentProvider->getContentVariantSegments();
        foreach ($segmentIterator as $segment) {
            $definition = json_decode($segment->getDefinition(), JSON_OBJECT_AS_ARRAY);
            if (isset($definition['filters'])
                && is_array($definition['filters'])
                && $this->hasRelationInFilters($definition['filters'], $joinIdentifierHelper)
            ) {
                yield $segment;
            }
        }
    }

    /**
     * @param array $filters
     * @param JoinIdentifierHelper $joinIdentifierHelper
     * @return bool
     */
    private function hasRelationInFilters(array $filters, JoinIdentifierHelper $joinIdentifierHelper): bool
    {
        foreach ($filters as $filter) {
            if (!is_array($filter)) {
                continue;
            }
            if (array_key_exists('columnName', $filter)) {
                $joinParts = $joinIdentifierHelper->explodeJoinIdentifier($filter['columnName']);
                if (count($joinParts) > 1) {
                    return true;
                }
            } elseif ($this->hasRelationInFilters($filter, $joinIdentifierHelper)) {
                return true;
            }
        }

        return false;
    }
}
