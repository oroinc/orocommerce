<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class BaseCombinedPriceListRelationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new BaseCombinedPriceListRelation(),
            [
                ['priceList', new CombinedPriceList()],
                ['fullChainPriceList', new CombinedPriceList()],
                ['website', new Website()]
            ]
        );
    }

    public function testUpdateFullChainPriceList()
    {
        $relation = new BaseCombinedPriceListRelation();
        $priceList = new CombinedPriceList();
        $relation->setPriceList($priceList);
        $this->assertSame($priceList, $relation->getFullChainPriceList());
    }
}
