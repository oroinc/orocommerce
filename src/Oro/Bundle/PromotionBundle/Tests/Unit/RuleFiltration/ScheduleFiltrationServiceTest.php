<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\CronBundle\Checker\ScheduleIntervalChecker;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionSchedule;
use Oro\Bundle\PromotionBundle\RuleFiltration\ScheduleFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ScheduleFiltrationServiceTest extends AbstractSkippableFiltrationServiceTest
{
    use EntityTrait;

    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $filtrationService;

    /**
     * @var ScheduleIntervalChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scheduleIntervalChecker;

    /**
     * @var ScheduleFiltrationService
     */
    protected $scheduleFiltrationService;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->scheduleIntervalChecker = $this->createMock(ScheduleIntervalChecker::class);
        $this->scheduleFiltrationService = new ScheduleFiltrationService(
            $this->filtrationService,
            $this->scheduleIntervalChecker
        );
    }

    public function testGetFilteredRuleOwners()
    {
        $promotionWithSuitableSchedule = $this->getEntity(Promotion::class, ['id' => 1]);
        $suitableSchedule = new PromotionSchedule();
        $promotionWithSuitableSchedule->addSchedule($suitableSchedule);

        $promotionWithUnsuitableSchedule = $this->getEntity(Promotion::class, ['id' => 2]);
        $unsuitableSchedule = new PromotionSchedule();
        $promotionWithUnsuitableSchedule->addSchedule($unsuitableSchedule);

        $promotionWithoutSchedule = $this->getEntity(Promotion::class, ['id' => 3]);

        $ruleOwners = [
            $promotionWithSuitableSchedule,
            $promotionWithUnsuitableSchedule,
            $promotionWithoutSchedule
        ];

        $expected = [
            $promotionWithSuitableSchedule,
            $promotionWithoutSchedule
        ];

        $this->scheduleIntervalChecker->expects($this->exactly(2))
            ->method('hasActiveSchedule')
            ->willReturnMap([
                [$promotionWithSuitableSchedule->getSchedules(), null, true],
                [$promotionWithUnsuitableSchedule->getSchedules(), null, false],
            ]);

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($expected)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->scheduleFiltrationService->getFilteredRuleOwners($ruleOwners, []));
    }

    public function testFilterIsSkippable()
    {
        $this->assertServiceSkipped($this->scheduleFiltrationService, $this->filtrationService);
    }
}
