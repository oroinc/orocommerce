<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Acl\Voter\ProductCollectionSegmentVoter;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProductCollectionSegmentVoterTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ContentVariantSegmentProvider&MockObject $contentVariantSegmentProvider;
    private TokenInterface&MockObject $token;
    private ProductCollectionSegmentVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->contentVariantSegmentProvider = $this->createMock(ContentVariantSegmentProvider::class);
        $this->token = $this->createMock(TokenInterface::class);

        $container = TestContainerBuilder::create()
            ->add(ContentVariantSegmentProvider::class, $this->contentVariantSegmentProvider)
            ->getContainer($this);

        $this->voter = new ProductCollectionSegmentVoter($this->doctrineHelper, $container);
        $this->voter->setClassName(Segment::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedAttribute(array $attributes): void
    {
        $segment = new Segment();

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->willReturn(1);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $segment, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedClass(array $attributes): void
    {
        $object = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testDeniedIfSegmentHasContentVariant(array $attributes): void
    {
        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->willReturn($segmentId);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(Segment::class, $segmentId)
            ->willReturn($segment);

        $this->contentVariantSegmentProvider->expects(self::once())
            ->method('hasContentVariant')
            ->with($segment)
            ->willReturn(true);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $segment, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testAbstainedIfSegmentHasNotContentVariant(array $attributes): void
    {
        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->willReturn($segmentId);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(Segment::class, $segmentId)
            ->willReturn($segment);

        $this->contentVariantSegmentProvider->expects(self::once())
            ->method('hasContentVariant')
            ->with($segment)
            ->willReturn(false);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $segment, $attributes)
        );
    }

    public function testSegmentStateToContentVariantCached(): void
    {
        $attributes = ['EDIT'];

        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->willReturn($segmentId);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(Segment::class, $segmentId)
            ->willReturn($segment);

        $this->contentVariantSegmentProvider->expects(self::once())
            ->method('hasContentVariant')
            ->with($segment)
            ->willReturn(true);

        $this->voter->vote($this->token, $segment, $attributes);
        $this->voter->vote($this->token, $segment, $attributes);
    }

    public static function supportedAttributesDataProvider(): array
    {
        return [
            [['EDIT']],
            [['DELETE']]
        ];
    }

    public static function unsupportedAttributesDataProvider(): array
    {
        return [
            [['VIEW']],
            [['CREATE']],
            [['ASSIGN']]
        ];
    }
}
