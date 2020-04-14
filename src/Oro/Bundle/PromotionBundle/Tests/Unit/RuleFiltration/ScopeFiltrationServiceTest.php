<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionType;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\RuleFiltration\ScopeFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Component\Testing\Unit\EntityTrait;

class ScopeFiltrationServiceTest extends AbstractSkippableFiltrationServiceTest
{
    use EntityTrait;

    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $filtrationService;

    /**
     * @var ScopeFiltrationService
     */
    protected $scopeFiltrationService;

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeManager;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $this->scopeFiltrationService = new ScopeFiltrationService(
            $this->filtrationService,
            $this->scopeManager
        );
    }

    public function testNotMatchedFiltered()
    {
        $scope = new Scope();

        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getScopes')
            ->willReturn(new ArrayCollection([$scope]));

        $context[ContextDataConverterInterface::CRITERIA] = new ScopeCriteria(
            [],
            $this->createMock(ClassMetadataFactory::class)
        );

        $this->scopeManager->expects($this->any())
            ->method('isScopeMatchCriteria')
            ->will($this->returnValue(false));

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn([]);

        $this->scopeFiltrationService->getFilteredRuleOwners([$promotion], $context);
    }

    public function testMatchedAllowed()
    {
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 1]);

        $promotion = $this->createMock(PromotionDataInterface::class);
        $promotion->expects($this->any())
            ->method('getScopes')
            ->willReturn(new ArrayCollection([$scope]));

        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => 5]);
        /** @var Scope $scope3 */
        $scope3 = $this->getEntity(Scope::class, ['id' => 2]);

        $promotion2 = $this->createMock(PromotionDataInterface::class);
        $promotion2->expects($this->any())
            ->method('getScopes')
            ->willReturn(new ArrayCollection([$scope2, $scope3]));

        $criteria = new ScopeCriteria([], $this->createMock(ClassMetadataFactory::class));
        $context[ContextDataConverterInterface::CRITERIA] = $criteria;

        $this->scopeManager->expects($this->any())
            ->method('isScopeMatchCriteria')
            ->willReturnMap([
                [$scope, $criteria, PromotionType::SCOPE_TYPE, false],
                [$scope2, $criteria, PromotionType::SCOPE_TYPE, true],
                [$scope3, $criteria, PromotionType::SCOPE_TYPE, false]
            ]);

        $expected = [$promotion2];
        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($expected, $context)
            ->willReturnArgument(0);

        $this->assertEquals(
            $expected,
            $this->scopeFiltrationService->getFilteredRuleOwners(
                [$promotion, $promotion2],
                $context
            )
        );
    }

    public function testGetFilteredRuleOwnersWhenNoScopes()
    {
        $promotion = new AppliedPromotionData();

        $context[ContextDataConverterInterface::CRITERIA] = new ScopeCriteria(
            [],
            $this->createMock(ClassMetadataFactory::class)
        );

        $this->scopeManager
            ->expects($this->never())
            ->method('isScopeMatchCriteria');

        $expected = [$promotion];
        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($expected, $context)
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->assertEquals($expected, $this->scopeFiltrationService->getFilteredRuleOwners([$promotion], $context));
    }

    public function testFilterIsSkippable()
    {
        $this->assertServiceSkipped($this->scopeFiltrationService, $this->filtrationService);
    }
}
