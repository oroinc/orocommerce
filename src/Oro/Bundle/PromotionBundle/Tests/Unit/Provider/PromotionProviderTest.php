<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
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
     * @var PromotionProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->ruleFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->contextDataConverter = $this->createMock(ContextDataConverterInterface::class);

        $this->provider = new PromotionProvider(
            $this->registry,
            $this->ruleFiltrationService,
            $this->contextDataConverter
        );
    }

    public function testGetPromotions()
    {
        $sourceEntity = new \stdClass();
        $filteredPromotion = new Promotion();
        $promotions = [$filteredPromotion, new Promotion()];
        $context = ['some context item'];

        $this->expectsPromotions($promotions);

        $this->contextDataConverter->expects($this->once())
            ->method('getContextData')
            ->with($sourceEntity)
            ->willReturn($context);

        $this->ruleFiltrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($promotions, $context)
            ->willReturn([$filteredPromotion]);

        $result = $this->provider->getPromotions($sourceEntity);
        $this->assertSame([$filteredPromotion], $result);
    }

    public function testIsPromotionAppliedWhenPromotionIsApplied()
    {
        $sourceEntity = new \stdClass();
        /** @var Promotion $promotion */
        $promotion = $this->getEntity(Promotion::class, ['id' => 5]);
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
        /** @var Promotion $anotherPromotion */
        $anotherPromotion = $this->getEntity(Promotion::class, ['id' => 7]);

        /** @var Promotion $promotion */
        $promotion = $this->getEntity(Promotion::class, ['id' => 5]);
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

        /** @var Promotion $promotion */
        $promotion = $this->getEntity(Promotion::class, ['id' => 5]);
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
        $promotion = $this->getEntity(Promotion::class, ['id' => 5]);
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
}
