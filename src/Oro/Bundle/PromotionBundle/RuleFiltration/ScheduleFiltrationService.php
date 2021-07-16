<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\CronBundle\Checker\ScheduleIntervalChecker;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class ScheduleFiltrationService extends AbstractSkippableFiltrationService
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @var ScheduleIntervalChecker
     */
    private $scheduleIntervalChecker;

    public function __construct(
        RuleFiltrationServiceInterface $filtrationService,
        ScheduleIntervalChecker $scheduleIntervalChecker
    ) {
        $this->filtrationService = $filtrationService;
        $this->scheduleIntervalChecker = $scheduleIntervalChecker;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredOwners = array_values(array_filter($ruleOwners, [$this, 'isScheduleEnabled']));

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    private function isScheduleEnabled(RuleOwnerInterface $ruleOwner): bool
    {
        if ($ruleOwner instanceof AppliedPromotionData) {
            return true;
        }

        return $ruleOwner instanceof PromotionDataInterface && $this->isPromotionApplicable($ruleOwner);
    }

    private function isPromotionApplicable(PromotionDataInterface $promotion): bool
    {
        return $promotion->getSchedules()->isEmpty()
            || $this->scheduleIntervalChecker->hasActiveSchedule($promotion->getSchedules());
    }
}
