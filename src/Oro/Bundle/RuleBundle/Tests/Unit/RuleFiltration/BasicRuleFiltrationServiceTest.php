<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\BasicRuleFiltrationService;

class BasicRuleFiltrationServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BasicRuleFiltrationService
     */
    private $service;

    protected function setUp()
    {
        $this->service = new BasicRuleFiltrationService();
    }

    public function testGetFilteredRuleOwners()
    {
        $context = [];

        $ruleOwners = [
            $this->getMock(RuleOwnerInterface::class, ['getRule']),
            $this->getMock(RuleOwnerInterface::class, ['getRule']),
        ];

        static::assertEquals($ruleOwners, $this->service->getFilteredRuleOwners($ruleOwners, $context));
    }
}
