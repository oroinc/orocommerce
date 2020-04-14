<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySku;

class ProductBySkuTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductBySku
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new ProductBySku();
    }

    public function testValidatedBy()
    {
        $this->assertEquals('oro_product_product_by_sku_validator', $this->constraint->validatedBy());
    }
}
