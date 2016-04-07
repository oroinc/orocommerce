<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class BaseCombinedPriceListRelationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new BaseCombinedPriceListRelation(),
            [
                ['priceList', new CombinedPriceList()],
                ['website', new Website()]
            ]
        );
    }
}
