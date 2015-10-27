<?php
namespace OroB2B\Bundle\InvoiceBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Class InvoiceLineItemEntityTest
 */
class InvoiceLineItemEntityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['product', new Product()],
            ['productSku', 'product-1234'],
            ['freeFormProduct', 'free form product'],
            ['quantity', 10],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'product-unit-code'],
            ['price', 9.99],
            ['invoice', new Invoice()],
        ];

        $invoiceLineItem = new InvoiceLineItem();
        $this->assertPropertyAccessors($invoiceLineItem, $properties);
    }
}
