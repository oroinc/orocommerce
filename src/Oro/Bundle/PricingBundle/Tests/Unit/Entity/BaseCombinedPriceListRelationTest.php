<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class BaseCombinedPriceListRelationTest extends \PHPUnit\Framework\TestCase
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
