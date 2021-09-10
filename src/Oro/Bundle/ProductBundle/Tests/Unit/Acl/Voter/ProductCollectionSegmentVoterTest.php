<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Acl\Voter\ProductCollectionSegmentVoter;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProductCollectionSegmentVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ContentVariantSegmentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contentVariantSegmentProvider;

    /** @var ProductCollectionSegmentVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->contentVariantSegmentProvider = $this->createMock(ContentVariantSegmentProvider::class);

        $container = TestContainerBuilder::create()
            ->add('oro_product.provider.content_variant_segment_provider', $this->contentVariantSegmentProvider)
            ->getContainer($this);

        $this->voter = new ProductCollectionSegmentVoter($this->doctrineHelper, $container);
        $this->voter->setClassName(Segment::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedAttribute(array $attributes)
    {
        $segment = new Segment();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->willReturn(1);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $segment, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedClass(array $attributes)
    {
        $object = new \stdClass;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testDeniedIfSegmentHasContentVariant(array $attributes)
    {
        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->willReturn($segmentId);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Segment::class, $segmentId)
            ->willReturn($segment);

        $this->contentVariantSegmentProvider->expects($this->any())
            ->method('hasContentVariant')
            ->with($segment)
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $segment, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testAbstainedIfSegmentHasNotContentVariant(array $attributes)
    {
        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->willReturn($segmentId);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Segment::class, $segmentId)
            ->willReturn($segment);

        $this->contentVariantSegmentProvider->expects($this->any())
            ->method('hasContentVariant')
            ->with($segment)
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
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
            ->willReturn($segmentId);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Segment::class, $segmentId)
            ->willReturn($segment);

        $this->contentVariantSegmentProvider->expects($this->once())
            ->method('hasContentVariant')
            ->with($segment)
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $this->voter->vote($token, $segment, $attributes);
        $this->voter->vote($token, $segment, $attributes);
    }

    public function supportedAttributesDataProvider(): array
    {
        return [
            [['EDIT']],
            [['DELETE']]
        ];
    }

    public function unsupportedAttributesDataProvider(): array
    {
        return [
            [['VIEW']],
            [['CREATE']],
            [['ASSIGN']]
        ];
    }
}
