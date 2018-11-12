<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

abstract class AbstractSkippableFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param RuleFiltrationServiceInterface $service
     * @param RuleFiltrationServiceInterface[]|\PHPUnit\Framework\MockObject\MockObject $decoratedService
     */
    protected function assertServiceSkipped(RuleFiltrationServiceInterface $service, $decoratedService)
    {
        $decoratedService
            ->expects($this->never())
            ->method('getFilteredRuleOwners');

        $ruleOwner = $this->createMock(RuleOwnerInterface::class);
        $service->getFilteredRuleOwners([$ruleOwner], ['skip_filters' => [get_class($service) => true]]);
    }
}
