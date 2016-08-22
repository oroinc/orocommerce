<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceListChangeTrigger;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListChangeTriggerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new PriceListChangeTrigger(),
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
        $trigger = new PriceListChangeTrigger();
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
