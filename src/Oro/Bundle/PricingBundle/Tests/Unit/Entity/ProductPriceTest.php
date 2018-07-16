<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductPriceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new ProductPrice(),
            [
                ['priceRule', new PriceRule()],
            ]
        );
    }
}
