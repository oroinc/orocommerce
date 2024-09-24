<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\RuleFiltration\DuplicateFiltrationService;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class DuplicateFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseFiltrationService;

    /** @var DuplicateFiltrationService */
    private $filtrationService;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);

        $this->filtrationService = new DuplicateFiltrationService($this->baseFiltrationService);
    }

    private function getRuleOwner(int $id): PromotionDataInterface
    {
        $ruleOwner = $this->createMock(PromotionDataInterface::class);
        $ruleOwner->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $ruleOwner;
    }

    public function testShouldBeSkippable(): void
    {
        $ruleOwners = [$this->createMock(RuleOwnerInterface::class)];

        $this->baseFiltrationService->expects(self::never())
            ->method('getFilteredRuleOwners');

        self::assertSame(
            $ruleOwners,
            $this->filtrationService->getFilteredRuleOwners(
                $ruleOwners,
                ['skip_filters' => [DuplicateFiltrationService::class => true]]
            )
        );
    }

    public function testGetFilteredRuleOwners(): void
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
        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($expected, $context)
            ->willReturnArgument(0);

        self::assertEquals(
            $expected,
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }
}
