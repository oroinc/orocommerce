<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\AppliedCouponsDataProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\Stub\AppliedCouponsAwareStub;
use Oro\Component\Testing\Unit\EntityTrait;

class AppliedCouponsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var AppliedCouponsDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->provider = new AppliedCouponsDataProvider($this->registry);
    }

    public function testGetAppliedCoupons()
    {
        $coupons = $this->createMock(Collection::class);
        $entity = $this->createMock(AppliedCouponsAwareStub::class);
        $entity->expects($this->once())
            ->method('getAppliedCoupons')
            ->willReturn($coupons);
        $this->assertSame($coupons, $this->provider->getAppliedCoupons($entity));
    }

    /**
     * @dataProvider trueFalseDataProvider
     */
    public function testHasAppliedCoupons(bool $isEmpty)
    {
        $coupons = $this->createMock(Collection::class);
        $coupons->expects($this->once())
            ->method('isEmpty')
            ->willReturn($isEmpty);
        $entity = $this->createMock(AppliedCouponsAwareStub::class);
        $entity->expects($this->once())
            ->method('getAppliedCoupons')
            ->willReturn($coupons);

        $this->assertSame(!$isEmpty, $this->provider->hasAppliedCoupons($entity));
    }

    public function trueFalseDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    public function testGetPromotionsForAppliedCoupons()
    {
        $coupons = new ArrayCollection(
            [
                $this->getEntity(AppliedCoupon::class, ['sourcePromotionId' => 3]),
                $this->getEntity(AppliedCoupon::class, ['sourcePromotionId' => 5])
            ]
        );
        $entity = $this->createMock(AppliedCouponsAwareStub::class);
        $entity->expects($this->once())
            ->method('getAppliedCoupons')
            ->willReturn($coupons);

        $promotions = [3 => $this->getEntity(Promotion::class, ['id' => 3])];
        $repo = $this->createMock(PromotionRepository::class);
        $repo->expects($this->once())
            ->method('getPromotionsWithLabelsByIds')
            ->with([3, 5])
            ->willReturn($promotions);
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Promotion::class)
            ->willReturn($repo);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Promotion::class)
            ->willReturn($em);

        $this->assertEquals($promotions, $this->provider->getPromotionsForAppliedCoupons($entity));
    }
}
