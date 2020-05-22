<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionAwareContentVariantEntityListener;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionVariantReindexMessageSendListener;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class ProductCollectionAwareContentVariantEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductCollectionVariantReindexMessageSendListener|\PHPUnit\Framework\MockObject\MockObject
     */
    private $reindexEventListener;

    /**
     * @var ProductCollectionDefinitionConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productCollectionDefinitionConverter;

    /**
     * @var ProductCollectionAwareContentVariantEntityListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->reindexEventListener = $this->createMock(ProductCollectionVariantReindexMessageSendListener::class);
        $this->productCollectionDefinitionConverter = $this->createMock(ProductCollectionDefinitionConverter::class);
        $this->listener = new ProductCollectionAwareContentVariantEntityListener(
            $this->reindexEventListener,
            $this->productCollectionDefinitionConverter
        );
    }

    public function testPostPersist()
    {
        $segment = new Segment();
        $this->reindexEventListener->expects($this->once())
            ->method('scheduleSegment')
            ->with($segment);

        $this->listener->postPersist($segment);
    }

    public function testPreRemove()
    {
        $segment = new Segment();
        $this->reindexEventListener->expects($this->once())
            ->method('scheduleMessageBySegmentDefinition')
            ->with($segment);

        $this->listener->preRemove($segment);
    }

    public function testPreUpdate()
    {
        $segment = new Segment();
        $changeSet = ['definition' => ['oldDefinition', 'newDefinition']];
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $args = new PreUpdateEventArgs($segment, $entityManager, $changeSet);

        $this->productCollectionDefinitionConverter->expects($this->exactly(2))
            ->method('getDefinitionParts')
            ->withConsecutive(['oldDefinition'], ['newDefinition'])
            ->willReturnOnConsecutiveCalls(
                [
                    ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY => '3,4',
                ],
                [
                    ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY => '4,5',
                ]
            );
        $this->reindexEventListener->expects($this->once())
            ->method('scheduleSegment')
            ->with($segment, false, [3, 5]);

        $this->listener->preUpdate($segment, $args);
    }

    public function testPreUpdateWhenDefinitionNotChanged()
    {
        $segment = new Segment();
        $changeSet = [];
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $args = new PreUpdateEventArgs($segment, $entityManager, $changeSet);

        $this->productCollectionDefinitionConverter->expects($this->never())
            ->method('getDefinitionParts');
        $this->reindexEventListener->expects($this->never())
            ->method('scheduleSegment');

        $this->listener->preUpdate($segment, $args);
    }
}
