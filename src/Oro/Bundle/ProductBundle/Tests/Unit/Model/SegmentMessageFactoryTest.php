<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SegmentMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SegmentRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $segmentRepository;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var SegmentMessageFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->registry = $this->createMock(RegistryInterface::class);
        $this->factory = new SegmentMessageFactory($this->registry);
    }

    public function testCreateMessage()
    {
        $this->expectsRegistryGetRepository();
        $segmentId = 777;
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['id' => $segmentId]);
        $websiteIds = [333];

        $message = $this->factory->createMessage($segment, $websiteIds);
        $this->assertEquals(
            [
                SegmentMessageFactory::ID => $segmentId,
                SegmentMessageFactory::WEBSITE_IDS => $websiteIds
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
        ]);
        $this->assertSame($expectedSegment, $segment);
    }

    public function testGetWebsiteIdsFromMessage()
    {
        $this->expectsRegistryGetRepository();
        $segmentId = 777;
        $websiteIds = [333];

        $segment = $this->factory->getWebsiteIdsFromMessage([
            SegmentMessageFactory::ID => $segmentId,
            SegmentMessageFactory::WEBSITE_IDS => $websiteIds,
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
        ]);
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
                'message' => 'The required options "id", "website_ids" are missing.',
            ],
            'with extra data' => [
                'data' => [
                    SegmentMessageFactory::ID => 777,
                    SegmentMessageFactory::WEBSITE_IDS => [888],
                    'someExtraData' => 888,
                ],
                'message' => 'The option "someExtraData" does not exist. Defined options are: "id", "website_ids".',
            ],
            'wrong data type for id' => [
                'data' => [
                    SegmentMessageFactory::ID => 'someString',
                    SegmentMessageFactory::WEBSITE_IDS => [888],
                ],
                'message' => 'The option "id" with value "someString" is expected to be of type "int",'
                    .' but is of type "string".',
            ],
            'wrong data type for website_id' => [
                'data' => [
                    SegmentMessageFactory::ID => 777,
                    SegmentMessageFactory::WEBSITE_IDS => 'someString',
                ],
                'message' => 'The option "website_ids" with value "someString" is expected to be of type "array",'
                    .' but is of type "string".',
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
