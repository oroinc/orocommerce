<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\AccumulateSegmentMessageFactory;
use Oro\Bundle\ProductBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;

class AccumulateSegmentMessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var SegmentRepository|\PHPUnit\Framework\MockObject\MockObject */
    private SegmentRepository $segmentRepository;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private ManagerRegistry $registry;
    private AccumulateSegmentMessageFactory $factory;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->factory = new AccumulateSegmentMessageFactory($this->registry);
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
                AccumulateSegmentMessageFactory::JOB_ID => $jobId,
                AccumulateSegmentMessageFactory::ID => $segmentId,
                AccumulateSegmentMessageFactory::WEBSITE_IDS => $websiteIds,
                AccumulateSegmentMessageFactory::DEFINITION => null,
                AccumulateSegmentMessageFactory::IS_FULL => $isFull,
                AccumulateSegmentMessageFactory::ADDITIONAL_PRODUCTS => $additionalProducts,
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
            AccumulateSegmentMessageFactory::JOB_ID => $jobId,
            AccumulateSegmentMessageFactory::ID => $segmentId,
            AccumulateSegmentMessageFactory::WEBSITE_IDS => [333],
            AccumulateSegmentMessageFactory::IS_FULL => true,
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
            AccumulateSegmentMessageFactory::JOB_ID => 11,
            AccumulateSegmentMessageFactory::ID => null,
            AccumulateSegmentMessageFactory::WEBSITE_IDS => [333],
            AccumulateSegmentMessageFactory::DEFINITION => 'segment definition',
            AccumulateSegmentMessageFactory::IS_FULL => true,
        ]);

        $this->assertEquals($expectedSegment, $segment);
    }

    public function testGetSegmentFromMessageWithoutSegmentAndDefinition()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Segment Id or Segment Definition should be present in message.');
        $this->factory->getSegmentFromMessage([
            AccumulateSegmentMessageFactory::JOB_ID => 11,
            AccumulateSegmentMessageFactory::WEBSITE_IDS => [888],
            AccumulateSegmentMessageFactory::IS_FULL => true,
        ]);
    }

    public function testGetJobIdFromMessage()
    {
        $this->expectsRegistryGetRepository();
        $jobId = 11;
        $segmentId = 777;
        $websiteIds = [333];

        $jobIdFromData = $this->factory->getJobIdFromMessage([
            AccumulateSegmentMessageFactory::JOB_ID => $jobId,
            AccumulateSegmentMessageFactory::ID => $segmentId,
            AccumulateSegmentMessageFactory::WEBSITE_IDS => $websiteIds,
            AccumulateSegmentMessageFactory::IS_FULL => true,
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
            AccumulateSegmentMessageFactory::JOB_ID => $jobId,
            AccumulateSegmentMessageFactory::ID => $segmentId,
            AccumulateSegmentMessageFactory::WEBSITE_IDS => $websiteIds,
            AccumulateSegmentMessageFactory::IS_FULL => true,
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
            AccumulateSegmentMessageFactory::JOB_ID => $jobId,
            AccumulateSegmentMessageFactory::ID => $id,
            AccumulateSegmentMessageFactory::WEBSITE_IDS => [888],
            AccumulateSegmentMessageFactory::IS_FULL => true,
        ]);
    }

    public function testGetAdditionalProductsFromMessage()
    {
        $this->expectsRegistryGetRepository();
        $additionalProducts = [42];

        $segment = $this->factory->getAdditionalProductsFromMessage([
            AccumulateSegmentMessageFactory::JOB_ID => 11,
            AccumulateSegmentMessageFactory::ID => 777,
            AccumulateSegmentMessageFactory::WEBSITE_IDS => [333],
            AccumulateSegmentMessageFactory::IS_FULL => true,
            AccumulateSegmentMessageFactory::ADDITIONAL_PRODUCTS => $additionalProducts,
        ]);
        $this->assertSame($additionalProducts, $segment);
    }

    /**
     * @dataProvider getIsFullProvider
     * @param bool $isFull
     * @param bool $expectedIsFull
     */
    public function testGetIsFull(bool $isFull, bool $expectedIsFull)
    {
        $this->expectsRegistryGetRepository();

        $segment = $this->factory->getIsFull([
            AccumulateSegmentMessageFactory::JOB_ID => 11,
            AccumulateSegmentMessageFactory::ID => 777,
            AccumulateSegmentMessageFactory::WEBSITE_IDS => [333],
            AccumulateSegmentMessageFactory::IS_FULL => $isFull,
        ]);
        $this->assertSame($expectedIsFull, $segment);
    }

    /**
     * @return array
     */
    public function getIsFullProvider()
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

    private function expectsRegistryGetRepository()
    {
        $this->segmentRepository = $this->createMock(SegmentRepository::class);
        $this->registry
            ->expects($this->any())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($this->segmentRepository);
    }
}
