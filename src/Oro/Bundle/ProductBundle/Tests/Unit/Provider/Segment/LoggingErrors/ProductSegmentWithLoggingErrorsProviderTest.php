<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider\Segment\LoggingErrors;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\Segment\LoggingErrors\ProductSegmentWithLoggingErrorsProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Psr\Log\LoggerInterface;

class ProductSegmentWithLoggingErrorsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SegmentManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $segmentManager;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var ProductSegmentWithLoggingErrorsProvider
     */
    private $provider;

    /**
     * @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenAccessor;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->organization = (new Organization())->setId(1);

        $this->segmentManager = $this->createMock(SegmentManager::class);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor
            ->expects($this->once())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new ProductSegmentWithLoggingErrorsProvider(
            $this->segmentManager,
            $this->logger,
            $this->tokenAccessor
        );
    }

    public function testGetProductSegmentById()
    {
        $segment = $this->createMock(Segment::class);
        $segment
            ->expects($this->once())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->segmentManager->expects(static::once())
            ->method('findById')
            ->with(1)
            ->willReturn($segment);

        $segment->expects(static::once())
            ->method('getEntity')
            ->willReturn(Product::class);

        self::assertSame($segment, $this->provider->getProductSegmentById(1));
    }

    public function testGetProductSegmentByIdNotFound()
    {
        $this->segmentManager->expects(static::once())
            ->method('findById')
            ->with(2)
            ->willReturn(null);

        $this->logger->expects(static::once())
            ->method('error')
            ->with('Segment was not found', ['id' => 2]);

        self::assertNull($this->provider->getProductSegmentById(2));
    }

    public function testGetProductSegmentByIdNotProduct()
    {
        $segment = $this->createMock(Segment::class);
        $segment
            ->expects($this->once())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->segmentManager->expects(static::once())
            ->method('findById')
            ->with(1)
            ->willReturn($segment);

        $segment
            ->method('getId')
            ->willReturn(1);

        $segment
            ->method('getName')
            ->willReturn('Segment name');

        $segment
            ->method('getEntity')
            ->willReturn('anotherClass');

        $segmentType = $this->createMock(SegmentType::class);

        $segment
            ->method('getType')
            ->willReturn($segmentType);

        $segmentType
            ->method('getName')
            ->willReturn('dynamic');

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                'Expected "Oro\Bundle\ProductBundle\Entity\Product", but "anotherClass" is given.',
                [
                    'id' => 1,
                    'name' => 'Segment name',
                    'entity' => 'anotherClass',
                    'type' => 'dynamic',
                ]
            );

        self::assertNull($this->provider->getProductSegmentById(1));
    }
}
