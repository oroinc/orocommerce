<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\RuleFiltration\DuplicateFiltrationService;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class DuplicateFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtrationService;

    /** @var DuplicateFiltrationService */
    private $duplicateFiltrationService;

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

    private function getRuleOwner(int $id): PromotionDataInterface
    {
        $ruleOwner = $this->createMock(PromotionDataInterface::class);
        $ruleOwner->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $ruleOwner;
    }

    public function testFilterIsSkippable()
    {
        $this->filtrationService->expects($this->never())
            ->method('getFilteredRuleOwners');

        $ruleOwner = $this->createMock(RuleOwnerInterface::class);
        $this->duplicateFiltrationService->getFilteredRuleOwners(
            [$ruleOwner],
            ['skip_filters' => [get_class($this->duplicateFiltrationService) => true]]
        );
    }
}
