<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CombinedPriceListTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->createPriceList(),
            [
                ['enabled', false],
                ['pricesCalculated', false],
            ]
        );
    }

    /**
     * @return CombinedPriceList
     */
    protected function createPriceList()
    {
        return new CombinedPriceList();
    }
}
