<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Acl\Voter\PromotionMatchedProductSegmentVoter;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PromotionMatchedProductSegmentVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PromotionMatchedProductSegmentVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->voter = new PromotionMatchedProductSegmentVoter($this->doctrineHelper);
        $this->voter->setClassName(Segment::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedAttribute(array $attributes)
    {
        $segment = new Segment();

        $this->doctrineHelper->expects($this->any())
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

        $this->doctrineHelper->expects($this->any())
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
    public function testDeniedIfSegmentIsAttachedToPromotion(array $attributes)
    {
        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->willReturn($segmentId);

        $foundPromo = $this->createMock(Promotion::class);
        $this->expectsRepositoryCalls($segmentId, $segment, $foundPromo);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $segment, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testAbstainedIfSegmentIsNotAttachedToPromotion(array $attributes)
    {
        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->willReturn($segmentId);

        $foundPromo = null;
        $this->expectsRepositoryCalls($segmentId, $segment, $foundPromo);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $segment, $attributes)
        );
    }

    public function testSegmentStateToPromotionCache()
    {
        $attributes = ['EDIT'];

        $segment = new Segment();
        $segmentId = 1;

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($segment, false)
            ->willReturn($segmentId);

        $foundPromo = $this->createMock(Promotion::class);
        $this->expectsRepositoryCalls($segmentId, $segment, $foundPromo);

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

    private function expectsRepositoryCalls(int $segmentId, Segment $segment, ?Promotion $foundPromo)
    {
        $repository = $this->createMock(PromotionRepository::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->with(Segment::class, $segmentId)
            ->willReturn($segment);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(Promotion::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findPromotionByProductSegment')
            ->with($segment)
            ->willReturn($foundPromo);
    }
}
