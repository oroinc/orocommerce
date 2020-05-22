<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\RuleFiltration\DuplicateFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class DuplicateFiltrationServiceTest extends AbstractSkippableFiltrationServiceTest
{
    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $filtrationService;

    /**
     * @var DuplicateFiltrationService
     */
    protected $duplicateFiltrationService;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);

        $this->duplicateFiltrationService = new DuplicateFiltrationService(
            $this->filtrationService
        );
    }

    public function testGetFilteredRuleOwners()
    {
        $ruleOwners = [
            $this->getRuleOwner(1),
            $this->getRuleOwner(1),
            $this->getRuleOwner(2),
            $this->getRuleOwner(3),
            $this->getRuleOwner(2),
            $this->getRuleOwner(5)
        ];

        $expected = [
            $this->getRuleOwner(1),
            $this->getRuleOwner(2),
            $this->getRuleOwner(3),
            $this->getRuleOwner(5)
        ];

        $context = [];
        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($expected, $context)
            ->willReturnArgument(0);

        $this->assertEquals(
            $expected,
            $this->duplicateFiltrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    /**
     * @param int $id
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getRuleOwner($id)
    {
        $ruleOwner = $this->createMock(PromotionDataInterface::class);
        $ruleOwner->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $ruleOwner;
    }

    public function testFilterIsSkippable()
    {
        $this->assertServiceSkipped($this->duplicateFiltrationService, $this->filtrationService);
    }
}
