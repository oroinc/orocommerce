<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;

class CombinedPriceListTest extends \PHPUnit_Framework_TestCase
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
