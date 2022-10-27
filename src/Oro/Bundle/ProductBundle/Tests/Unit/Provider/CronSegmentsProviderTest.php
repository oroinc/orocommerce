<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\ProductBundle\Provider\CronSegmentsProvider;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class CronSegmentsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContentVariantSegmentProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentVariantSegmentProvider;

    /**
     * @var CronSegmentsProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->contentVariantSegmentProvider = $this->createMock(ContentVariantSegmentProvider::class);
        $this->provider = new CronSegmentsProvider($this->contentVariantSegmentProvider);
    }

    public function testGetSegmentsWithRelations()
    {
        $emptySegment = $this->createSegment([]);
        $segmentWithNonArrayFilters = $this->createSegment(['filters' => 'string']);
        $simpleSegment = $this->createSegment(['filters' => [['columnName' => 'column']]]);
        $segmentWithRelation1 = $this->createSegment([
            'filters' => [
                ['columnName' => 'column+SomeClass::id'],
            ],
        ]);
        $segmentWithRelation2 = $this->createSegment([
            'filters' => [
                ['columnName' => 'column+SomeClass::id'],
                'AND',
                ['columnName' => 'newColumn+SomeOtherClass::id'],
            ],
        ]);
        $segmentWithRelation3 = $this->createSegment([
            'filters' => [
                [
                    ['columnName' => 'column+SomeClass::id'],
                    'AND',
                    ['columnName' => 'newColumn+SomeOtherClass::id'],
                ]
            ],
        ]);
        $segmentWithCriteria = $this->createSegment([
            'filters' => [
                [
                    ['columnName' => 'simpleColumn', 'criteria' => 'condition-data-audit'],
                ]
            ],
        ]);
        $this->contentVariantSegmentProvider->expects($this->once())
            ->method('getContentVariantSegments')
            ->willReturn([
                $emptySegment,
                $simpleSegment,
                $segmentWithRelation1,
                $segmentWithRelation2,
                $segmentWithRelation3,
                $segmentWithNonArrayFilters,
                $segmentWithCriteria
            ]);

        $expectedSegments = [$segmentWithRelation1, $segmentWithRelation2, $segmentWithRelation3, $segmentWithCriteria];
        $this->assertEquals($expectedSegments, iterator_to_array($this->provider->getSegments()));
    }

    /**
     * @param array $definition
     * @return Segment
     */
    private function createSegment(array $definition)
    {
        $segment = new Segment();
        $segment->setDefinition(QueryDefinitionUtil::encodeDefinition($definition));

        return $segment;
    }
}
