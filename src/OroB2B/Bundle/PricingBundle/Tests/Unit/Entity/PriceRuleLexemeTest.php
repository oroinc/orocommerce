<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
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
