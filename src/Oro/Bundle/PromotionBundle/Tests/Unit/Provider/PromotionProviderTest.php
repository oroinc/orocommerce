<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\RuleFiltration\AbstractSkippableFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class PromotionProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ruleFiltrationService;

    /**
     * @var ContextDataConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextDataConverter;

    /**
     * @var AppliedPromotionMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $promotionMapper;

    /**
     * @var PromotionProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->ruleFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->contextDataConverter = $this->createMock(ContextDataConverterInterface::class);
        $this->promotionMapper = $this->createMock(AppliedPromotionMapper::class);

        $this->provider = new PromotionProvider(
            $this->registry,
            $this->ruleFiltrationService,
            $this->contextDataConverter,
            $this->promotionMapper
        );
    }

    public function testGetPromotions()
    {
        $appliedPromotionEntity1 = $this->getEntity(AppliedPromotion::class, ['promotionData' => ['some data']]);
        $appliedPromotionEntity2 = $this->getEntity(AppliedPromotion::class, ['promotionData' => ['some data']]);
        $appliedPromotionEntity3 = new AppliedPromotion();

        $appliedPromotion1 = $this->createMock(AppliedPromotionData::class);
        $appliedPromotion2 = $this->createMock(AppliedPromotionData::class);

        $this->promotionMapper->expects($this->exactly(2))
            ->method('mapAppliedPromotionToPromotionData')
            ->withConsecutive(
                [$appliedPromotionEntity1],
                [$appliedPromotionEntity2]
            )
            ->willReturnOnConsecutiveCalls(
                $appliedPromotion1,
                $appliedPromotion2
            );

        /** @var AppliedPromotionsAwareInterface|\PHPUnit\Framework\MockObject\MockObject $sourceEntity */
        $sourceEntity = $this->createMock(AppliedPromotionsAwareInterface::class);
        $sourceEntity->expects($this->any())
            ->method('getAppliedPromotions')
            ->willReturn(new ArrayCollection([
                $appliedPromotionEntity1,
                $appliedPromotionEntity2,
                $appliedPromotionEntity3
            ]));

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
        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $promotion */
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
        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $anotherPromotion */
        $anotherPromotion = $this->createMock(PromotionDataInterface::class);
        $anotherPromotion->expects($this->any())
            ->method('getId')
            ->willReturn(7);

        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $promotion */
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

    public function testIsPromotionApplicableWhenPromotionIsApplicable()
    {
        $sourceEntity = new \stdClass();

        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $promotion */
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

    public function testIsPromotionApplicableWhenPromotionIsNotApplicable()
    {
        $sourceEntity = new \stdClass();

        /** @var Promotion $promotion */
        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $promotion */
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

    public function testIsPromotionApplicableWithSkippedFilters()
    {
        $sourceEntity = new \stdClass();
        $skippedFilters = ['SomeFilterClass'];

        /** @var Promotion $promotion */
        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $promotion */
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $context = ['some context item'];

        $this->contextDataConverter->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($context);

        $expectedContext = [
            'some context item',
            AbstractSkippableFiltrationService::SKIP_FILTERS_KEY => $skippedFilters
        ];

        $this->ruleFiltrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], $expectedContext)
            ->willReturn([]);

        $this->assertFalse($this->provider->isPromotionApplicable($sourceEntity, $promotion, $skippedFilters));
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
}
