<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
                ['force', true]
            ]
        );
    }
}
