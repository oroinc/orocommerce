<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

abstract class AbstractSkippableFiltrationServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param RuleFiltrationServiceInterface $service
     * @param RuleFiltrationServiceInterface[]|\PHPUnit_Framework_MockObject_MockObject $decoratedService
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
