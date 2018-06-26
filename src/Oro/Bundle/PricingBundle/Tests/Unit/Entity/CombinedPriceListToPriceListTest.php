<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CombinedPriceListToPriceListTest extends \PHPUnit\Framework\TestCase
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
