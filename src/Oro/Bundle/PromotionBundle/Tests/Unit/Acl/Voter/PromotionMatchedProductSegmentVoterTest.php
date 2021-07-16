<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Acl\Voter\ProductCollectionSegmentVoter;
use Oro\Bundle\PromotionBundle\Acl\Voter\PromotionMatchedProductSegmentVoter;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PromotionMatchedProductSegmentVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PromotionMatchedProductSegmentVoter
     */
    private $voter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->voter = new PromotionMatchedProductSegmentVoter($this->doctrineHelper);
        $this->voter->setClassName(Segment::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     * @param array $attributes
     */
    public function testAbstainOnUnsupportedAttribute($attributes)
    {
        $segment = new Segment();

        $this->assertDoctrineHelperCalls($segment, 1);

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

        $this->assertDoctrineHelperCalls($object, 1);

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
    public function testDeniedIfSegmentIsAttachedToPromotion($attributes)
    {
        $segment = new Segment();
        $segmentId = 1;

        $this->assertDoctrineHelperCalls($segment, $segmentId);

        $foundPromo = $this->createMock(Promotion::class);
        $repository = $this->createMock(PromotionRepository::class);
        $this->assertRepositoryCalls($segmentId, $segment, $repository, $foundPromo);

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
    public function testAbstainedIfSegmentIsNotAttachedToPromotion($attributes)
    {
        $segment = new Segment();
        $segmentId = 1;

        $this->assertDoctrineHelperCalls($segment, $segmentId);

        $foundPromo = null;
        $repository = $this->createMock(PromotionRepository::class);
        $this->assertRepositoryCalls($segmentId, $segment, $repository, $foundPromo);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductCollectionSegmentVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $segment, $attributes)
        );
    }

    public function testSegmentStateToPromotionCache()
    {
        $attributes = ['EDIT'];

        $segment = new Segment();
        $segmentId = 1;

        $this->assertDoctrineHelperCalls($segment, $segmentId);

        $foundPromo = $this->createMock(Promotion::class);
        $repository = $this->createMock(PromotionRepository::class);
        $this->assertRepositoryCalls($segmentId, $segment, $repository, $foundPromo);

        /** @var TokenInterface $token */
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

    /**
     * @param object $object
     * @param int $id
     */
    private function assertDoctrineHelperCalls($object, $id)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn($id);
    }

    /**
     * @param int $segmentId
     * @param Segment $segment
     * @param PromotionRepository|\PHPUnit\Framework\MockObject\MockObject $repository
     * @param null|Promotion $foundPromo
     */
    private function assertRepositoryCalls(
        $segmentId,
        Segment $segment,
        PromotionRepository $repository,
        Promotion $foundPromo = null
    ) {
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
