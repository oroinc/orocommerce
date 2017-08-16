<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\RuleFiltration\ScopeFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Component\Testing\Unit\EntityTrait;

class ScopeFiltrationServiceTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filtrationService;

    /**
     * @var ScopeFiltrationService
     */
    protected $scopeFiltrationService;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeManager;

    protected function setUp()
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
            ->willReturn([$scope]);

        $context[ContextDataConverterInterface::CRITERIA] = new ScopeCriteria([], []);

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
            ->willReturn([$scope]);

        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => 5]);
        /** @var Scope $scope3 */
        $scope3 = $this->getEntity(Scope::class, ['id' => 2]);

        $promotion2 = $this->createMock(PromotionDataInterface::class);
        $promotion2->expects($this->any())
            ->method('getScopes')
            ->willReturn([$scope2, $scope3]);

        $context[ContextDataConverterInterface::CRITERIA] = new ScopeCriteria([], []);

        $this->scopeManager->expects($this->any())
            ->method('isScopeMatchCriteria')
            ->willReturnCallback(function (Scope $scope) {
                switch ($scope->getId()) {
                    case 1:
                        return false;
                        break;
                    case 2:
                        return true;
                        break;
                    case 3:
                        return false;
                        break;
                }

                return false;
            });

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$promotion2], $context)
            ->willReturn([$promotion2]);

        $this->scopeFiltrationService->getFilteredRuleOwners([$promotion, $promotion2], $context);
    }
}
