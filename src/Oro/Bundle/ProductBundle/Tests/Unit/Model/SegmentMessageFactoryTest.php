<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;

class SegmentMessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var SegmentRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $segmentRepository;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var SegmentMessageFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->factory = new SegmentMessageFactory($this->registry);
    }

    public function testCreateMessage()
    {
        $this->expectsRegistryGetRepository();
        $jobId = 11;
        $segmentId = 777;
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['id' => $segmentId]);
        $websiteIds = [333];
        $isFull = false;
        $additionalProducts = [42];

        $message = $this->factory->createMessage(
            $jobId,
            $websiteIds,
            $segment,
            null,
            $isFull,
            $additionalProducts
        );
        $this->assertEquals(
            [
                ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID => $jobId,
                ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => $segmentId,
                ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => $websiteIds,
                ReindexProductCollectionBySegmentTopic::OPTION_NAME_DEFINITION => null,
                ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => $isFull,
                ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS => $additionalProducts,
            ],
            $message
        );
    }

    public function testGetSegmentFromMessage()
    {
        $this->expectsRegistryGetRepository();
        $jobId = 11;
        $segmentId = 777;
        $expectedSegment = new Segment();

        $this->segmentRepository->expects($this->once())
            ->method('find')
            ->with($segmentId)
            ->willReturn($expectedSegment);

        $segment = $this->factory->getSegmentFromMessage([
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID => $jobId,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => $segmentId,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => [333],
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => true,
        ]);
        $this->assertSame($expectedSegment, $segment);
    }

    public function testGetSegmentFromMessageWithDefinition()
    {
        $expectedSegment = new Segment();
        $expectedSegment->setEntity(Product::class);
        $expectedSegment->setDefinition('segment definition');

        $this->registry->expects($this->never())
            ->method($this->anything());

        $segment = $this->factory->getSegmentFromMessage([
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID => 11,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => null,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => [333],
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_DEFINITION => 'segment definition',
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => true,
        ]);

        $this->assertEquals($expectedSegment, $segment);
    }

    public function testGetJobIdFromMessage()
    {
        $this->expectsRegistryGetRepository();
        $jobId = 11;
        $segmentId = 777;
        $websiteIds = [333];

        $jobIdFromData = $this->factory->getJobIdFromMessage([
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID => $jobId,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => $segmentId,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => $websiteIds,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => true,
        ]);
        $this->assertSame($jobId, $jobIdFromData);
    }

    public function testGetWebsiteIdsFromMessage()
    {
        $this->expectsRegistryGetRepository();
        $jobId = 11;
        $segmentId = 777;
        $websiteIds = [333];

        $segment = $this->factory->getWebsiteIdsFromMessage([
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID => $jobId,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => $segmentId,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => $websiteIds,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => true,
        ]);
        $this->assertSame($websiteIds, $segment);
    }

    public function testGetSegmentFromMessageWhenSegmentDoesNotExsists()
    {
        $this->expectsRegistryGetRepository();
        $id = 777;
        $jobId = 11;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No segment exists with id "777"');
        $this->segmentRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $this->factory->getSegmentFromMessage([
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID => $jobId,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => $id,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => [888],
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => true,
        ]);
    }

    public function testGetAdditionalProductsFromMessage()
    {
        $this->expectsRegistryGetRepository();
        $additionalProducts = [42];

        $segment = $this->factory->getAdditionalProductsFromMessage([
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID => 11,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => 777,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => [333],
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => true,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS => $additionalProducts,
        ]);
        $this->assertSame($additionalProducts, $segment);
    }

    /**
     * @dataProvider getIsFullProvider
     */
    public function testGetIsFull(bool $isFull, bool $expectedIsFull)
    {
        $this->expectsRegistryGetRepository();

        $segment = $this->factory->getIsFull([
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID => 11,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => 777,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => [333],
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => $isFull,
        ]);
        $this->assertSame($expectedIsFull, $segment);
    }

    public function getIsFullProvider(): array
    {
        return [
            'is full true' => [
                'isFull' => true,
                'expectedIsFull' => true,
            ],
            'is full false' => [
                'isFull' => false,
                'expectedIsFull' => false,
            ],
        ];
    }

    private function expectsRegistryGetRepository(): void
    {
        $this->segmentRepository = $this->createMock(SegmentRepository::class);
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($this->segmentRepository);
    }
}
