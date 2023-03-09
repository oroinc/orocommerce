<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\RuleFiltration\AbstractSkippableFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
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

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var PromotionProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->ruleFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->contextDataConverter = $this->createMock(ContextDataConverterInterface::class);
        $this->promotionMapper = $this->createMock(AppliedPromotionMapper::class);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor
            ->expects($this->any())
            ->method('getOrganizationId')
            ->willReturn(1);

        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);
        $this->memoryCacheProvider
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(fn ($cacheKeyArguments, $callback) => $callback());

        $this->provider = new PromotionProvider(
            $this->registry,
            $this->ruleFiltrationService,
            $this->contextDataConverter,
            $this->promotionMapper
        );

        $this->provider->setTokenAccessor($this->tokenAccessor);
        $this->provider->setMemoryCacheProvider($this->memoryCacheProvider);
    }

    public function testGetPromotions(): void
    {
        $appliedPromotionEntity1 = $this->getEntity(AppliedPromotion::class, ['promotionData' => ['some data']]);
        $appliedPromotionEntity2 = $this->getEntity(AppliedPromotion::class, ['promotionData' => ['some data']]);
        $appliedPromotionEntity3 = new AppliedPromotion();

        $appliedPromotion1 = $this->createMock(AppliedPromotionData::class);
        $appliedPromotion2 = $this->createMock(AppliedPromotionData::class);

        $this->promotionMapper
            ->expects($this->exactly(2))
            ->method('mapAppliedPromotionToPromotionData')
            ->willReturnMap([
                [$appliedPromotionEntity1, $appliedPromotion1],
                [$appliedPromotionEntity2, $appliedPromotion2]
            ]);

        /** @var AppliedPromotionsAwareInterface|\PHPUnit\Framework\MockObject\MockObject $sourceEntity */
        $sourceEntity = $this->createMock(AppliedPromotionsAwareInterface::class);
        $sourceEntity
            ->expects($this->any())
            ->method('getAppliedPromotions')
            ->willReturn(new ArrayCollection([
                $appliedPromotionEntity1,
                $appliedPromotionEntity2,
                $appliedPromotionEntity3
            ]));

        $filteredPromotion = $this->createMock(PromotionDataInterface::class);
        $promotions = [$filteredPromotion, $this->createMock(PromotionDataInterface::class)];

        $this->assertPromotions($promotions);
        $this->contextDataConverter
            ->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn([]);

        $this->ruleFiltrationService
            ->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with(array_merge([$appliedPromotion1, $appliedPromotion2], $promotions), [])
            ->willReturn([$appliedPromotion1, $appliedPromotion2, $filteredPromotion]);

        $result = $this->provider->getPromotions($sourceEntity);
        $this->assertSame([$appliedPromotion1, $appliedPromotion2, $filteredPromotion], $result);
    }

    public function testGetPromotionsWhenNoOrganizationInSecurityContext(): void
    {
        $appliedPromotionEntity1 = new AppliedPromotion();
        $appliedPromotionEntity1->setPromotionData(['some data']);
        $appliedPromotionEntity2 = new AppliedPromotion();
        $appliedPromotionEntity2->setPromotionData(['some data']);
        $appliedPromotionEntity3 = new AppliedPromotion();

        $appliedPromotion1 = $this->createMock(AppliedPromotionData::class);
        $appliedPromotion2 = $this->createMock(AppliedPromotionData::class);

        $this->promotionMapper
            ->expects($this->exactly(2))
            ->method('mapAppliedPromotionToPromotionData')
            ->willReturnMap([
                [$appliedPromotionEntity1, $appliedPromotion1],
                [$appliedPromotionEntity2, $appliedPromotion2]
            ]);

        $sourceEntity = $this->createMock(AppliedPromotionsAwareInterface::class);
        $sourceEntity
            ->expects($this->any())
            ->method('getAppliedPromotions')
            ->willReturn(new ArrayCollection([
                $appliedPromotionEntity1,
                $appliedPromotionEntity2,
                $appliedPromotionEntity3
            ]));

        // All promotions with the not corresponding organization will be filtered in query.
        $this->assertPromotions([]);

        $this->contextDataConverter
            ->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn([]);

        $this->ruleFiltrationService
            ->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$appliedPromotion1, $appliedPromotion2], [])
            ->willReturn([$appliedPromotion1, $appliedPromotion2]);

        $result = $this->provider->getPromotions($sourceEntity);
        $this->assertSame([$appliedPromotion1, $appliedPromotion2], $result);
    }

    public function testIsPromotionAppliedWhenPromotionIsApplied(): void
    {
        $sourceEntity = new \stdClass();
        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $promotion */
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion
            ->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $promotions = [$promotion];

        $this->assertPromotions($promotions);
        $this->contextDataConverter
            ->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn([]);

        $this->ruleFiltrationService
            ->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($promotions, [])
            ->willReturn($promotions);

        $this->assertTrue($this->provider->isPromotionApplied($sourceEntity, $promotion));
    }

    public function testIsPromotionAppliedWhenPromotionIsNotApplied(): void
    {
        $sourceEntity = new \stdClass();
        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $anotherPromotion */
        $anotherPromotion = $this->createMock(PromotionDataInterface::class);
        $anotherPromotion
            ->expects($this->any())
            ->method('getId')
            ->willReturn(7);

        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $promotion */
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion
            ->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $promotions = [$anotherPromotion];

        $this->assertPromotions($promotions);
        $this->contextDataConverter
            ->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn([]);

        $this->ruleFiltrationService
            ->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($promotions, [])
            ->willReturn($promotions);

        $this->assertFalse($this->provider->isPromotionApplied($sourceEntity, $promotion));
    }

    public function testIsPromotionApplicableWhenPromotionIsApplicable(): void
    {
        $sourceEntity = new \stdClass();
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion
            ->expects($this->any())
            ->method('getId')
            ->willReturn(5);

        $this->contextDataConverter
            ->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn([]);

        $this->ruleFiltrationService
            ->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], [])
            ->willReturn([$promotion]);

        $this->assertTrue($this->provider->isPromotionApplicable($sourceEntity, $promotion));
    }

    public function testIsPromotionApplicableWhenPromotionIsNotApplicable(): void
    {
        $sourceEntity = new \stdClass();

        /** @var Promotion $promotion */
        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $promotion */
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion
            ->expects($this->any())
            ->method('getId')
            ->willReturn(5);

        $this->contextDataConverter
            ->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn([]);

        $this->ruleFiltrationService
            ->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], [])
            ->willReturn([]);

        $this->assertFalse($this->provider->isPromotionApplicable($sourceEntity, $promotion));
    }

    public function testIsPromotionApplicableWithSkippedFilters(): void
    {
        $sourceEntity = new \stdClass();
        $skippedFilters = ['SomeFilterClass'];

        /** @var Promotion $promotion */
        /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject $promotion */
        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion
            ->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $context = ['some context item'];

        $this->contextDataConverter
            ->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($context);

        $expectedContext = [
            'some context item',
            AbstractSkippableFiltrationService::SKIP_FILTERS_KEY => $skippedFilters
        ];

        $this->ruleFiltrationService
            ->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion], $expectedContext)
            ->willReturn([]);

        $this->assertFalse($this->provider->isPromotionApplicable($sourceEntity, $promotion, $skippedFilters));
    }

    /**
     * @param array|Promotion[] $promotions
     */
    private function assertPromotions(array $promotions): void
    {
        $repository = $this->createMock(PromotionRepository::class);
        $repository
            ->expects($this->once())
            ->method('getAvailablePromotions')
            ->willReturn($promotions);

        $this->registry
            ->expects($this->once())
            ->method('getRepository')
            ->with(Promotion::class)
            ->willReturn($repository);
    }
}
