<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\Provider\AvailablePromotionProviderInterface;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\RuleFiltration\AbstractSkippableFiltrationService;
use Oro\Bundle\PromotionBundle\Tests\Unit\Stub\AppliedPromotionsAwareStub;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class PromotionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextDataConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextDataConverter;

    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ruleFiltrationService;

    /** @var AppliedPromotionMapper|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionMapper;

    /** @var AvailablePromotionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $availablePromotionProvider;

    /** @var PromotionAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionAwareHelper;

    /** @var PromotionProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->contextDataConverter = $this->createMock(ContextDataConverterInterface::class);
        $this->ruleFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->promotionMapper = $this->createMock(AppliedPromotionMapper::class);
        $this->availablePromotionProvider = $this->createMock(AvailablePromotionProviderInterface::class);
        $this->promotionAwareHelper = $this->createMock(PromotionAwareEntityHelper::class);

        $memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);
        $memoryCacheProvider->expects(self::any())
            ->method('get')
            ->willReturnCallback(fn ($cacheKeyArguments, $callback) => $callback());

        $this->provider = new PromotionProvider(
            $this->contextDataConverter,
            $this->ruleFiltrationService,
            $this->promotionMapper,
            $this->availablePromotionProvider,
            $this->promotionAwareHelper,
            $memoryCacheProvider
        );
    }

    public function testGetPromotions(): void
    {
        $appliedPromotion1 = new AppliedPromotion();
        $appliedPromotion1->setPromotionData(['some data']);
        $appliedPromotion2 = new AppliedPromotion();
        $appliedPromotion2->setPromotionData(['some data']);
        $appliedPromotion3 = new AppliedPromotion();

        $appliedPromotionData1 = $this->createMock(AppliedPromotionData::class);
        $appliedPromotionData2 = $this->createMock(AppliedPromotionData::class);

        $this->promotionAwareHelper->expects(self::any())
            ->method('isPromotionAware')
            ->willReturn(true);

        $sourceEntity = $this->createMock(AppliedPromotionsAwareStub::class);
        $sourceEntity->expects(self::any())
            ->method('getAppliedPromotions')
            ->willReturn(new ArrayCollection([$appliedPromotion1, $appliedPromotion2, $appliedPromotion3]));

        $this->promotionMapper->expects(self::exactly(2))
            ->method('mapAppliedPromotionToPromotionData')
            ->willReturnMap([
                [$appliedPromotion1, $appliedPromotionData1],
                [$appliedPromotion2, $appliedPromotionData2]
            ]);

        $filteredPromotion = $this->createMock(PromotionDataInterface::class);
        $promotions = [$filteredPromotion, $this->createMock(PromotionDataInterface::class)];

        $contextData = ['key' => 'val'];
        $this->contextDataConverter->expects(self::once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($contextData);

        $this->availablePromotionProvider->expects(self::once())
            ->method('getAvailablePromotions')
            ->with($contextData)
            ->willReturn($promotions);

        $this->ruleFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with(array_merge([$appliedPromotionData1, $appliedPromotionData2], $promotions), $contextData)
            ->willReturn([$appliedPromotionData1, $appliedPromotionData2, $filteredPromotion]);

        self::assertSame(
            [$appliedPromotionData1, $appliedPromotionData2, $filteredPromotion],
            $this->provider->getPromotions($sourceEntity)
        );
    }

    public function testGetPromotionsWhenNoOrganizationInSecurityContext(): void
    {
        $appliedPromotion1 = new AppliedPromotion();
        $appliedPromotion1->setPromotionData(['some data']);
        $appliedPromotion2 = new AppliedPromotion();
        $appliedPromotion2->setPromotionData(['some data']);
        $appliedPromotion3 = new AppliedPromotion();

        $appliedPromotionData1 = $this->createMock(AppliedPromotionData::class);
        $appliedPromotionData2 = $this->createMock(AppliedPromotionData::class);

        $this->promotionAwareHelper->expects(self::any())
            ->method('isPromotionAware')
            ->willReturn(true);

        $sourceEntity = $this->createMock(AppliedPromotionsAwareStub::class);
        $sourceEntity->expects(self::any())
            ->method('getAppliedPromotions')
            ->willReturn(new ArrayCollection([$appliedPromotion1, $appliedPromotion2, $appliedPromotion3]));

        $this->promotionMapper->expects(self::exactly(2))
            ->method('mapAppliedPromotionToPromotionData')
            ->willReturnMap([
                [$appliedPromotion1, $appliedPromotionData1],
                [$appliedPromotion2, $appliedPromotionData2]
            ]);

        $contextData = ['key' => 'val'];
        $this->contextDataConverter->expects(self::once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($contextData);

        // All promotions with the not corresponding organization will be filtered in query.
        $this->availablePromotionProvider->expects(self::once())
            ->method('getAvailablePromotions')
            ->with($contextData)
            ->willReturn([]);

        $this->ruleFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([$appliedPromotionData1, $appliedPromotionData2], $contextData)
            ->willReturn([$appliedPromotionData1, $appliedPromotionData2]);

        self::assertSame(
            [$appliedPromotionData1, $appliedPromotionData2],
            $this->provider->getPromotions($sourceEntity)
        );
    }

    public function testIsPromotionAppliedWhenPromotionIsApplied(): void
    {
        $sourceEntity = new \stdClass();
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects(self::any())
            ->method('getId')
            ->willReturn(5);
        $promotions = [$promotion];

        $contextData = ['key' => 'val'];
        $this->contextDataConverter->expects(self::once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($contextData);

        $this->availablePromotionProvider->expects(self::once())
            ->method('getAvailablePromotions')
            ->with($contextData)
            ->willReturn($promotions);

        $this->ruleFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($promotions, $contextData)
            ->willReturn($promotions);

        self::assertTrue($this->provider->isPromotionApplied($sourceEntity, $promotion));
    }

    public function testIsPromotionAppliedWhenPromotionIsNotApplied(): void
    {
        $sourceEntity = new \stdClass();
        $anotherPromotion = $this->createMock(PromotionDataInterface::class);
        $anotherPromotion->expects(self::any())
            ->method('getId')
            ->willReturn(7);

        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects(self::any())
            ->method('getId')
            ->willReturn(5);
        $promotions = [$anotherPromotion];

        $contextData = ['key' => 'val'];
        $this->contextDataConverter->expects(self::once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($contextData);

        $this->availablePromotionProvider->expects(self::once())
            ->method('getAvailablePromotions')
            ->with($contextData)
            ->willReturn($promotions);

        $this->ruleFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($promotions, $contextData)
            ->willReturn($promotions);

        self::assertFalse($this->provider->isPromotionApplied($sourceEntity, $promotion));
    }

    public function testIsPromotionApplicableWhenPromotionIsApplicable(): void
    {
        $sourceEntity = new \stdClass();
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects(self::any())
            ->method('getId')
            ->willReturn(5);

        $contextData = ['key' => 'val'];
        $this->contextDataConverter->expects(self::once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($contextData);

        $this->ruleFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], $contextData)
            ->willReturn([$promotion]);

        self::assertTrue($this->provider->isPromotionApplicable($sourceEntity, $promotion));
    }

    public function testIsPromotionApplicableWhenPromotionIsNotApplicable(): void
    {
        $sourceEntity = new \stdClass();

        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects(self::any())
            ->method('getId')
            ->willReturn(5);

        $contextData = ['key' => 'val'];
        $this->contextDataConverter->expects(self::once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($contextData);

        $this->ruleFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], $contextData)
            ->willReturn([]);

        self::assertFalse($this->provider->isPromotionApplicable($sourceEntity, $promotion));
    }

    public function testIsPromotionApplicableWithSkippedFilters(): void
    {
        $sourceEntity = new \stdClass();
        $skippedFilters = ['SomeFilterClass'];

        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects(self::any())
            ->method('getId')
            ->willReturn(5);

        $contextData = ['key' => 'val'];
        $this->contextDataConverter->expects(self::once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($contextData);

        $this->ruleFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with(
                [$promotion],
                array_merge($contextData, [AbstractSkippableFiltrationService::SKIP_FILTERS_KEY => $skippedFilters])
            )
            ->willReturn([]);

        self::assertFalse($this->provider->isPromotionApplicable($sourceEntity, $promotion, $skippedFilters));
    }
}
