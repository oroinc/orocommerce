<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class PromotionProviderTest extends \PHPUnit_Framework_TestCase
{
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
}
