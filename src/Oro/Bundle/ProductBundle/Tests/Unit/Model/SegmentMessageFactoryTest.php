<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;

class SegmentMessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var SegmentRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $segmentRepository;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var SegmentMessageFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->factory = new SegmentMessageFactory($this->registry);
    }

    public function testCreateMessage()
    {
        $this->expectsRegistryGetRepository();
        $segmentId = 777;
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['id' => $segmentId]);
        $websiteIds = [333];
        $isFull = false;
        $additionalProducts = [42];

        $message = $this->factory->createMessage($websiteIds, $segment, null, $isFull, $additionalProducts);
        $this->assertEquals(
            [
                SegmentMessageFactory::ID => $segmentId,
                SegmentMessageFactory::WEBSITE_IDS => $websiteIds,
                SegmentMessageFactory::DEFINITION => null,
                SegmentMessageFactory::IS_FULL => $isFull,
                SegmentMessageFactory::ADDITIONAL_PRODUCTS => $additionalProducts,
            ],
            $message
        );
    }

    public function testGetSegmentFromMessage()
    {
        $this->expectsRegistryGetRepository();
        $segmentId = 777;
        $expectedSegment = new Segment();

        $this->segmentRepository->expects($this->once())
            ->method('find')
            ->with($segmentId)
            ->willReturn($expectedSegment);

        $segment = $this->factory->getSegmentFromMessage([
            SegmentMessageFactory::ID => $segmentId,
            SegmentMessageFactory::WEBSITE_IDS => [333],
            SegmentMessageFactory::IS_FULL => true,
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
            SegmentMessageFactory::ID => null,
            SegmentMessageFactory::WEBSITE_IDS => [333],
            SegmentMessageFactory::DEFINITION => 'segment definition',
            SegmentMessageFactory::IS_FULL => true,
        ]);

        $this->assertEquals($expectedSegment, $segment);
    }

    public function testGetSegmentFromMessageWithoutSegmentAndDefinition()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Segment Id or Segment Definition should be present in message.');
        $this->factory->getSegmentFromMessage([
            SegmentMessageFactory::WEBSITE_IDS => [888],
            SegmentMessageFactory::IS_FULL => true,
        ]);
    }

    public function testGetWebsiteIdsFromMessage()
    {
        $this->expectsRegistryGetRepository();
        $segmentId = 777;
        $websiteIds = [333];

        $segment = $this->factory->getWebsiteIdsFromMessage([
            SegmentMessageFactory::ID => $segmentId,
            SegmentMessageFactory::WEBSITE_IDS => $websiteIds,
            SegmentMessageFactory::IS_FULL => true,
        ]);
        $this->assertSame($websiteIds, $segment);
    }

    public function testGetSegmentFromMessageWhenSegmentDoesNotExsists()
    {
        $this->expectsRegistryGetRepository();
        $id = 777;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No segment exists with id "777"');
        $this->segmentRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $this->factory->getSegmentFromMessage([
            SegmentMessageFactory::ID => $id,
            SegmentMessageFactory::WEBSITE_IDS => [888],
            SegmentMessageFactory::IS_FULL => true,
        ]);
    }

    public function testGetAdditionalProductsFromMessage()
    {
        $this->expectsRegistryGetRepository();
        $additionalProducts = [42];

        $segment = $this->factory->getAdditionalProductsFromMessage([
            SegmentMessageFactory::ID => 777,
            SegmentMessageFactory::WEBSITE_IDS => [333],
            SegmentMessageFactory::IS_FULL => true,
            SegmentMessageFactory::ADDITIONAL_PRODUCTS => $additionalProducts,
        ]);
        $this->assertSame($additionalProducts, $segment);
    }

    /**
     * @dataProvider getSegmentFromMessageWhenThrowsExceptionProvider
     * @param array $data
     * @param string $exceptionMessage
     */
    public function testGetSegmentFromMessageWhenThrowsException(
        array $data,
        $exceptionMessage
    ) {
        $this->expectsRegistryGetRepository();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->factory->getSegmentFromMessage($data);
    }

    /**
     * @return array
     */
    public function getSegmentFromMessageWhenThrowsExceptionProvider()
    {
        return [
            'without required data' => [
                'data' => [],
                'message' => 'The required options "is_full", "website_ids" are missing.',
            ],
            'with extra data' => [
                'data' => [
                    SegmentMessageFactory::ID => 777,
                    SegmentMessageFactory::WEBSITE_IDS => [888],
                    SegmentMessageFactory::DEFINITION => 'segment definition',
                    SegmentMessageFactory::IS_FULL => true,
                    SegmentMessageFactory::ADDITIONAL_PRODUCTS => [42],
                    'someExtraData' => 888,
                ],
                'message' => 'The option "someExtraData" does not exist.'
                    . ' Defined options are: "additional_products", "definition", "id", "is_full", "website_ids".'
            ],
            'wrong data type for id' => [
                'data' => [
                    SegmentMessageFactory::ID => 'someString',
                    SegmentMessageFactory::WEBSITE_IDS => [888],
                    SegmentMessageFactory::DEFINITION => 'segment definition',
                    SegmentMessageFactory::IS_FULL => true,
                    SegmentMessageFactory::ADDITIONAL_PRODUCTS => [42],
                ],
                'message' => 'The option "id" with value "someString" is expected to be of type "null" or "int",'
                    .' but is of type "string".',
            ],
            'wrong data type for definition' => [
                'data' => [
                    SegmentMessageFactory::ID => 42,
                    SegmentMessageFactory::WEBSITE_IDS => [888],
                    SegmentMessageFactory::DEFINITION => true,
                    SegmentMessageFactory::IS_FULL => true,
                    SegmentMessageFactory::ADDITIONAL_PRODUCTS => [42],
                ],
                'message' => 'The option "definition" with value true is expected to be of type "null" or "string",'
                    .' but is of type "boolean".',
            ],
            'wrong data type for website_id' => [
                'data' => [
                    SegmentMessageFactory::ID => null,
                    SegmentMessageFactory::WEBSITE_IDS => 'someString',
                    SegmentMessageFactory::DEFINITION => 'segment definition',
                    SegmentMessageFactory::IS_FULL => true,
                    SegmentMessageFactory::ADDITIONAL_PRODUCTS => [42],
                ],
                'message' => 'The option "website_ids" with value "someString" is expected to be of type "array",'
                    .' but is of type "string".',
            ],
            'wrong data type for is_full' => [
                'data' => [
                    SegmentMessageFactory::ID => null,
                    SegmentMessageFactory::WEBSITE_IDS => [777],
                    SegmentMessageFactory::DEFINITION => 'segment definition',
                    SegmentMessageFactory::IS_FULL => 'someString',
                    SegmentMessageFactory::ADDITIONAL_PRODUCTS => [42],
                ],
                'message' => 'The option "is_full" with value "someString" is expected to be of type "boolean",'
                    .' but is of type "string".',
            ],
            'wrong data type for additional_products' => [
                'data' => [
                    SegmentMessageFactory::ID => null,
                    SegmentMessageFactory::WEBSITE_IDS => [777],
                    SegmentMessageFactory::DEFINITION => 'segment definition',
                    SegmentMessageFactory::IS_FULL => false,
                    SegmentMessageFactory::ADDITIONAL_PRODUCTS => 'someString',
                ],
                'message' => 'The option "additional_products" with value "someString" is expected to be of'
                    .' type "array", but is of type "string".',
            ],
        ];
    }

    /**
     * @dataProvider getIsFullProvider
     * @param bool $isFull
     * @param bool $expectedIsFull
     */
    public function testGetIsFull($isFull, $expectedIsFull)
    {
        $this->expectsRegistryGetRepository();

        $segment = $this->factory->getIsFull([
            SegmentMessageFactory::ID => 777,
            SegmentMessageFactory::WEBSITE_IDS => [333],
            SegmentMessageFactory::IS_FULL => $isFull,
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
        $this->segmentRepository = $this->getMockBuilder(SegmentRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($this->segmentRepository);
    }
}
