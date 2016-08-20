<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;

class CombinedPriceListToPriceListTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new CombinedPriceListToPriceList(),
            [
                ['combinedPriceList', new CombinedPriceList()],
                ['priceList', new PriceList()],
                ['mergeAllowed', true],
                ['sortOrder', 10],
            ]
        );
    }
}
