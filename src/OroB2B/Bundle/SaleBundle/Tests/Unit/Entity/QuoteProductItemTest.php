<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;

class QuoteProductItemTest extends EntityTestCase
{
    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 123],
            ['quoteProduct', new QuoteProduct()],
            ['productUnit', new ProductUnit()],
            ['quantity', 11],
            ['price', new Price()],
        ];

        $this->assertPropertyAccessors(new QuoteProductItem(), $properties);
    }
}
