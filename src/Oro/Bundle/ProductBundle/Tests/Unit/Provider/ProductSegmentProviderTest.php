<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Psr\Log\LoggerInterface;

class ProductSegmentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SegmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $segmentManager;

    /** @var OrganizationRestrictionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationRestrictionProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ProductSegmentProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->segmentManager = $this->createMock(SegmentManager::class);
        $this->organizationRestrictionProvider = $this->createMock(OrganizationRestrictionProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new ProductSegmentProvider(
            $this->segmentManager,
            $this->organizationRestrictionProvider,
            $this->logger
        );
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    public function testGetProductSegmentById()
    {
        $organization = $this->getOrganization(1);

        $segment = $this->createMock(Segment::class);
        $segment->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $segment->expects(self::once())
            ->method('getEntity')
            ->willReturn(Product::class);

        $this->segmentManager->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn($segment);

        $this->organizationRestrictionProvider->expects(self::once())
            ->method('isEnabledOrganization')
            ->with(self::identicalTo($organization))
            ->willReturn(true);

        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame($segment, $this->provider->getProductSegmentById(1));
    }

    public function testGetProductSegmentByIdForSegmentFromNotActiveOrganization()
    {
        $organization = $this->getOrganization(1);

        $segment = $this->createMock(Segment::class);
        $segment->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $segment->expects(self::once())
            ->method('getEntity')
            ->willReturn(Product::class);

        $this->segmentManager->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn($segment);

        $this->organizationRestrictionProvider->expects(self::once())
            ->method('isEnabledOrganization')
            ->with(self::identicalTo($organization))
            ->willReturn(false);

        $this->logger->expects(self::never())
            ->method('error');

        self::assertNull($this->provider->getProductSegmentById(1));
    }

    public function testGetProductSegmentByIdWhenSegmentNotFound()
    {
        $this->segmentManager->expects(self::once())
            ->method('findById')
            ->with(2)
            ->willReturn(null);

        $this->organizationRestrictionProvider->expects(self::never())
            ->method('isEnabledOrganization');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Segment was not found', ['id' => 2]);

        self::assertNull($this->provider->getProductSegmentById(2));
    }

    public function testGetProductSegmentByIdForNotProductSegment()
    {
        $segmentType = $this->createMock(SegmentType::class);
        $segmentType->expects(self::any())
            ->method('getName')
            ->willReturn('dynamic');

        $segment = $this->createMock(Segment::class);
        $segment->expects(self::any())
            ->method('getId')
            ->willReturn(1);
        $segment->expects(self::any())
            ->method('getName')
            ->willReturn('Segment name');
        $segment->expects(self::any())
            ->method('getEntity')
            ->willReturn('anotherClass');
        $segment->expects(self::any())
            ->method('getType')
            ->willReturn($segmentType);
        $segment->expects(self::never())
            ->method('getOrganization');

        $this->segmentManager->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn($segment);

        $this->organizationRestrictionProvider->expects(self::never())
            ->method('isEnabledOrganization');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Expected segment for "Oro\Bundle\ProductBundle\Entity\Product", but "anotherClass" is given.',
                [
                    'id'     => 1,
                    'name'   => 'Segment name',
                    'entity' => 'anotherClass',
                    'type'   => 'dynamic',
                ]
            );

        self::assertNull($this->provider->getProductSegmentById(1));
    }
}
