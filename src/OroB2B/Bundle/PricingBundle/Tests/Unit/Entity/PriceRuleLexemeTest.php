<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PriceRuleLexemeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new PriceRuleLexeme(), [
            ['id', 42],
            ['className', 'some string'],
            ['fieldName', 'some string'],
            ['priceRule', new PriceRule()],
            ['priceList', new PriceList()],
            ['relationId', 42]
        ]);
    }
}
