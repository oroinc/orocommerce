<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscountsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Normalizer\NormalizerInterface;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class PromotionProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ruleFiltrationService;

    /**
     * @var ContextDataConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextDataConverter;

    /**
     * @var NormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $promotionNormalizer;

    /**
     * @var PromotionProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->ruleFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->contextDataConverter = $this->createMock(ContextDataConverterInterface::class);
        $this->promotionNormalizer = $this->createMock(NormalizerInterface::class);

        $this->provider = new PromotionProvider(
            $this->registry,
            $this->ruleFiltrationService,
            $this->contextDataConverter,
            $this->promotionNormalizer
        );
    }

    public function testGetPromotions()
    {
        $discountConfig = ['amount' => 10.0];
        $discountType = 'order';
        $promotionConfig1 = ['id' => 1];
        $promotionConfig2 = ['id' => 2];
        /** @var Promotion $promotion1 */
        $promotion1 = $this->getEntity(Promotion::class, ['id' => 1]);
        /** @var Promotion $promotion2 */
        $promotion2 = $this->getEntity(Promotion::class, ['id' => 2]);

        $appliedDiscount1 = new AppliedDiscount();
        $appliedDiscount1->setType($discountType)
            ->setAmount(10.0)
            ->setPromotion($promotion1)
            ->setConfigOptions($discountConfig)
            ->setPromotionData($promotionConfig1);
        $appliedDiscount2 = new AppliedDiscount();
        $appliedDiscount2->setType($discountType)
            ->setAmount(20.0)
            ->setPromotion($promotion2)
            ->setConfigOptions($discountConfig)
            ->setPromotionData($promotionConfig2);
        $appliedDiscount1Double = new AppliedDiscount();
        $appliedDiscount1Double->setType($discountType)
            ->setAmount(15.0)
            ->setPromotion($promotion1)
            ->setConfigOptions($discountConfig)
            ->setPromotionData($promotionConfig1);
        $appliedDiscount2Double = new AppliedDiscount();
        $appliedDiscount2Double->setType($discountType)
            ->setAmount(15.0)
            ->setConfigOptions($discountConfig)
            ->setPromotionData($promotionConfig2);

        $appliedPromotion1 = $this->getAppliedPromotionMock(1);
        $appliedPromotion2 = $this->getAppliedPromotionMock(2);

        $this->promotionNormalizer->expects($this->exactly(3))
            ->method('denormalize')
            ->withConsecutive(
                [$promotionConfig1],
                [$promotionConfig2],
                [$promotionConfig2]
            )
            ->willReturnOnConsecutiveCalls(
                $appliedPromotion1,
                $appliedPromotion2,
                $appliedPromotion2
            );

        /** @var AppliedDiscountsAwareInterface|\PHPUnit_Framework_MockObject_MockObject $sourceEntity */
        $sourceEntity = $this->createMock(AppliedDiscountsAwareInterface::class);
        $sourceEntity->expects($this->any())
            ->method('getAppliedDiscounts')
            ->willReturn([$appliedDiscount1, $appliedDiscount2, $appliedDiscount1Double, $appliedDiscount2Double]);

        $filteredPromotion = $this->createMock(PromotionDataInterface::class);
        $promotions = [$filteredPromotion, $this->createMock(PromotionDataInterface::class)];
        $context = ['some context item'];

        $this->expectsPromotions($promotions);

        $this->contextDataConverter->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($context);

        $this->ruleFiltrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with(array_merge([$appliedPromotion1, $appliedPromotion2], $promotions), $context)
            ->willReturn([$appliedPromotion1, $appliedPromotion2, $filteredPromotion]);

        $result = $this->provider->getPromotions($sourceEntity);
        $this->assertSame([$appliedPromotion1, $appliedPromotion2, $filteredPromotion], $result);
    }

    public function testIsPromotionAppliedWhenPromotionIsApplied()
    {
        $sourceEntity = new \stdClass();
        /** @var PromotionDataInterface|\PHPUnit_Framework_MockObject_MockObject $promotion */
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $promotions = [$promotion];
        $context = ['some context item'];

        $this->expectsPromotions($promotions);

        $this->contextDataConverter->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($context);

        $this->ruleFiltrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($promotions, $context)
            ->willReturn($promotions);

        $this->assertTrue($this->provider->isPromotionApplied($sourceEntity, $promotion));
    }

    public function testIsPromotionAppliedWhenPromotionIsNotApplied()
    {
        $sourceEntity = new \stdClass();
        /** @var PromotionDataInterface|\PHPUnit_Framework_MockObject_MockObject $anotherPromotion */
        $anotherPromotion = $this->createMock(PromotionDataInterface::class);
        $anotherPromotion->expects($this->any())
            ->method('getId')
            ->willReturn(7);

        /** @var PromotionDataInterface|\PHPUnit_Framework_MockObject_MockObject $promotion */
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $promotions = [$anotherPromotion];
        $context = ['some context item'];

        $this->expectsPromotions($promotions);

        $this->contextDataConverter->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($context);

        $this->ruleFiltrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($promotions, $context)
            ->willReturn($promotions);

        $this->assertFalse($this->provider->isPromotionApplied($sourceEntity, $promotion));
    }

    public function testIsPromotionAppliedWhenPromotionIsApplicable()
    {
        $sourceEntity = new \stdClass();

        /** @var PromotionDataInterface|\PHPUnit_Framework_MockObject_MockObject $promotion */
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $context = ['some context item'];

        $this->contextDataConverter->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($context);

        $this->ruleFiltrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], $context)
            ->willReturn([$promotion]);

        $this->assertTrue($this->provider->isPromotionApplicable($sourceEntity, $promotion));
    }

    public function testIsPromotionAppliedWhenPromotionIsNotApplicable()
    {
        $sourceEntity = new \stdClass();

        /** @var Promotion $promotion */
        /** @var PromotionDataInterface|\PHPUnit_Framework_MockObject_MockObject $promotion */
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $context = ['some context item'];

        $this->contextDataConverter->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($context);

        $this->ruleFiltrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], $context)
            ->willReturn([]);

        $this->assertFalse($this->provider->isPromotionApplicable($sourceEntity, $promotion));
    }

    /**
     * @param array|Promotion[] $promotions
     */
    private function expectsPromotions(array $promotions)
    {
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($promotions);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with(Promotion::class)
            ->willReturn($objectRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Promotion::class)
            ->willReturn($objectManager);
    }

    /**
     * @param int $id
     * @return AppliedPromotion|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getAppliedPromotionMock($id)
    {
        /** @var AppliedPromotion|\PHPUnit_Framework_MockObject_MockObject $appliedPromotion1 */
        $appliedPromotion1 = $this->createMock(AppliedPromotion::class);
        $appliedPromotion1->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $appliedPromotion1->expects($this->once())
            ->method('setDiscountConfiguration')
            ->with($this->isInstanceOf(DiscountConfiguration::class))
            ->willReturnSelf();

        return $appliedPromotion1;
    }
}
