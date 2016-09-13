<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToAccount;

class CombinedPriceListToAccountTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new CombinedPriceListToAccount(),
            [
                ['account', new Account()]
            ]
        );
    }
}
