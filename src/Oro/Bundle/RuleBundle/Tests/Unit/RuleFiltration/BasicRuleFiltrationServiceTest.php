<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\BasicRuleFiltrationService;

class BasicRuleFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BasicRuleFiltrationService
     */
    private $service;

    protected function setUp(): void
    {
        $this->service = new BasicRuleFiltrationService();
    }

    public function testGetFilteredRuleOwners()
    {
        $context = [];

        $ruleOwners = [
            $this->createPartialMock(RuleOwnerInterface::class, ['getRule']),
            $this->createPartialMock(RuleOwnerInterface::class, ['getRule']),
        ];

        static::assertEquals($ruleOwners, $this->service->getFilteredRuleOwners($ruleOwners, $context));
    }
}
