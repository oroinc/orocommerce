<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Entity;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

class RequestProductTest extends AbstractTest
{
    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['request', new Request()],
            ['product', new Product()],
        ];

        $this->assertPropertyAccessors(new RequestProduct(), $properties);
    }

    public function testSetProduct()
    {
        $product        = (new Product())->setSku('rfp-sku');
        $requestProduct = new RequestProduct();

        $this->assertNull($requestProduct->getProductSku());

        $requestProduct->setProduct($product);

        $this->assertEquals($product->getSku(), $requestProduct->getProductSku());
    }

    public function testAddRequestProductItem()
    {
        $requestProduct     = new RequestProduct();
        $requestProductItem = new RequestProductItem();

        $this->assertNull($requestProductItem->getRequestProduct());

        $requestProduct->addRequestProductItem($requestProductItem);

        $this->assertEquals($requestProduct, $requestProductItem->getRequestProduct());
    }
}
