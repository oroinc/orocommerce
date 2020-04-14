<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Acl\Voter\ProductCollectionSegmentVoter;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductCollectionSegmentVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductCollectionSegmentVoter
     */
    private $voter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContentVariantSegmentProvider
     */
    private $contentVariantSegmentProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->contentVariantSegmentProvider = $this->createMock(ContentVariantSegmentProvider::class);
        $this->voter = new ProductCollectionSegmentVoter($this->doctrineHelper, $this->contentVariantSegmentProvider);
        $this->voter->setClassName(Segment::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     * @param array $attributes
     */
    public function testAbstainOnUnsupportedAttribute($attributes)
    {
        $segment = new Segment();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->will($this->returnValue(1));

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductCollectionSegmentVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $segment, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     * @param array $attributes
     */
    public function testAbstainOnUnsupportedClass($attributes)
    {
        $object = new \stdClass;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->will($this->returnValue(1));

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductCollectionSegmentVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     * @param array $attributes
     */
    public function testDeniedIfSegmentHasContentVariant($attributes)
    {
        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->will($this->returnValue($segmentId));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Segment::class, $segmentId)
            ->willReturn($segment);

        $this->contentVariantSegmentProvider->expects($this->any())
            ->method('hasContentVariant')
            ->with($segment)
            ->willReturn(true);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductCollectionSegmentVoter::ACCESS_DENIED,
            $this->voter->vote($token, $segment, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     * @param array $attributes
     */
    public function testAbstainedIfSegmentHasNotContentVariant($attributes)
    {
        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->will($this->returnValue($segmentId));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Segment::class, $segmentId)
            ->willReturn($segment);

        $this->contentVariantSegmentProvider->expects($this->any())
            ->method('hasContentVariant')
            ->with($segment)
            ->willReturn(false);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductCollectionSegmentVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $segment, $attributes)
        );
    }

    public function testSegmentStateToContentVariantCached()
    {
        $attributes = ['EDIT'];

        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->will($this->returnValue($segmentId));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Segment::class, $segmentId)
            ->willReturn($segment);

        $this->contentVariantSegmentProvider->expects($this->once())
            ->method('hasContentVariant')
            ->with($segment)
            ->willReturn(true);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->voter->vote($token, $segment, $attributes);
        $this->voter->vote($token, $segment, $attributes);
    }

    /**
     * @return array
     */
    public function supportedAttributesDataProvider()
    {
        return [
            [['EDIT']],
            [['DELETE']]
        ];
    }

    /**
     * @return array
     */
    public function unsupportedAttributesDataProvider()
    {
        return [
            [['VIEW']],
            [['CREATE']],
            [['ASSIGN']]
        ];
    }
}
