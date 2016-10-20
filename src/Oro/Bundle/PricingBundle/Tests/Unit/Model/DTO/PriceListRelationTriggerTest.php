<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PriceListRelationTriggerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new PriceListRelationTrigger(),
            [
                ['website', new Website()],
                ['account', new Account()],
                ['accountGroup', new AccountGroup()],
                ['force', true],
            ]
        );
    }

    public function testToArray()
    {
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->getMock(Website::class);
        $website->method('getId')->willReturn(1);
        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMock(Account::class);
        $account->method('getId')->willReturn(1);
        /** @var AccountGroup|\PHPUnit_Framework_MockObject_MockObject $accountGroup */
        $accountGroup = $this->getMock(AccountGroup::class);
        $accountGroup->method('getId')->willReturn(1);
        $trigger = new PriceListRelationTrigger();
        $trigger->setWebsite($website)
            ->setAccount($account)
            ->setAccountGroup($accountGroup);

        $this->assertEquals(
            [
                'website' => 1,
                'account' => 1,
                'accountGroup' => 1,
                'force' => false,
            ],
            $trigger->toArray()
        );
    }
}
