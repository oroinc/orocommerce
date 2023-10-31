<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\BasicRuleFiltrationService;

class BasicRuleFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testGetFilteredRuleOwners(): void
    {
        $ruleOwners = [$this->createMock(RuleOwnerInterface::class)];

        $filtrationService = new BasicRuleFiltrationService();
        self::assertSame($ruleOwners, $filtrationService->getFilteredRuleOwners($ruleOwners, []));
    }
}
