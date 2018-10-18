<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CombinedPriceListActivationRuleTest extends \PHPUnit\Framework\TestCase
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
