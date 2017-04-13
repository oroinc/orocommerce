<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\ProductBundle\Provider\SegmentWithRelationsProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentWithRelationsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentVariantSegmentProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contentVariantSegmentProvider;

    /**
     * @var SegmentWithRelationsProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->contentVariantSegmentProvider = $this->createMock(ContentVariantSegmentProvider::class);
        $this->provider = new SegmentWithRelationsProvider($this->contentVariantSegmentProvider);
    }

    public function testGetSegmentsWithRelations()
    {
        $emptySegment = new Segment();
        $segmentWithNonArrayFilters = new Segment();
        $segmentWithNonArrayFilters->setDefinition(json_encode(['filters' => 'string']));
        $simpleSegment = new Segment();
        $simpleSegment->setDefinition(json_encode(['filters' => [['columnName' => 'column']]]));
        $segmentWithRelation1 = new Segment();
        $segmentWithRelation1->setDefinition(json_encode([
            'filters' => [
                ['columnName' => 'column+SomeClass::id'],
            ],
        ]));
        $segmentWithRelation2 = new Segment();
        $segmentWithRelation2->setDefinition(json_encode([
            'filters' => [
                ['columnName' => 'column+SomeClass::id'],
                ['columnName' => 'newColumn+SomeOtherClass::id'],
            ],
        ]));
        $this->contentVariantSegmentProvider->expects($this->once())
            ->method('getContentVariantSegments')
            ->willReturn([$emptySegment, $simpleSegment, $segmentWithRelation1, $segmentWithRelation2]);

        $generator = $this->provider->getSegmentsWithRelations();
        $actual = iterator_to_array($generator);
        $this->assertCount(2, $actual);
        $this->assertEquals([$segmentWithRelation1, $segmentWithRelation2], $actual);
    }
}
