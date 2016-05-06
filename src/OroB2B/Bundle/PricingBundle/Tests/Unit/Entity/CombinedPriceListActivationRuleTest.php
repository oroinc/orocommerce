<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;

class CombinedPriceListActivationRuleTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new CombinedPriceListActivationRule(),
            [
                ['combinedPriceList', new CombinedPriceList()],
                ['fullChainPriceList', new CombinedPriceList()],
                ['expireAt', new \DateTime()]
            ]
        );
    }
}
