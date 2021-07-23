<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * This class provides segments which use relations and/or not simple criteria (e.g. data-audit, segment) and therefore
 * cannot be tracked for immediate reindexation so they're reindexed by cron command.
 */
class CronSegmentsProvider
{
    /**
     * @var ContentVariantSegmentProvider
     */
    private $contentVariantSegmentProvider;

    public function __construct(ContentVariantSegmentProvider $contentVariantSegmentProvider)
    {
        $this->contentVariantSegmentProvider = $contentVariantSegmentProvider;
    }

    /**
     * @return \Generator|Segment[]
     */
    public function getSegments()
    {
        $joinIdentifierHelper = new JoinIdentifierHelper(Product::class);
        $segmentIterator = $this->contentVariantSegmentProvider->getContentVariantSegments();
        foreach ($segmentIterator as $segment) {
            $definition = QueryDefinitionUtil::decodeDefinition($segment->getDefinition());
            if (isset($definition['filters'])
                && is_array($definition['filters'])
                && $this->hasRelationOrCriteriaInFilters($definition['filters'], $joinIdentifierHelper)
            ) {
                yield $segment;
            }
        }
    }

    private function hasRelationOrCriteriaInFilters(array $filters, JoinIdentifierHelper $joinIdentifierHelper): bool
    {
        foreach ($filters as $filter) {
            if (!is_array($filter)) {
                continue;
            }
            if (array_key_exists('criteria', $filter)) {
                return true;
            }
            if (array_key_exists('columnName', $filter)) {
                $joinParts = $joinIdentifierHelper->explodeJoinIdentifier($filter['columnName']);
                if (count($joinParts) > 1) {
                    return true;
                }
            } elseif ($this->hasRelationOrCriteriaInFilters($filter, $joinIdentifierHelper)) {
                return true;
            }
        }

        return false;
    }
}
