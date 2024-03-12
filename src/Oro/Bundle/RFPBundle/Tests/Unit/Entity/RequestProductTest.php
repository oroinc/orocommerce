<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class RequestProductTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123],
            ['request', new Request()],
            ['product', new Product()],
            ['productSku', 'rfp-sku'],
            ['comment', 'comment'],
        ];

        $entity = new RequestProduct();
        self::assertPropertyAccessors($entity, $properties);

        self::assertPropertyCollections($entity, [
            ['requestProductItems', new RequestProductItem()],
        ]);
    }

    public function testKitItemLineItems(): void
    {
        $entity = new RequestProduct();

        $productKitItem = new ProductKitItemStub(42);
        $kitItemLineItem = (new RequestProductKitItemLineItem())
            ->setKitItem($productKitItem);

        self::assertSame([], $entity->getKitItemLineItems()->toArray());

        $entity->addKitItemLineItem($kitItemLineItem);
        self::assertSame(
            [$productKitItem->getId() => $kitItemLineItem],
            $entity->getKitItemLineItems()->toArray()
        );

        $entity->removeKitItemLineItem($kitItemLineItem);
        self::assertSame([], $entity->getKitItemLineItems()->toArray());
    }

    public function testGetEntityIdentifier(): void
    {
        $request = new RequestProduct();

        $id = 123;
        ReflectionUtil::setId($request, $id);
        self::assertSame($id, $request->getEntityIdentifier());
    }

    /**
     * @depends testProperties
     */
    public function testSetProduct(): void
    {
        $product = (new Product())->setSku('rfp-sku');
        $requestProduct = new RequestProduct();

        self::assertNull($requestProduct->getProductSku());

        $requestProduct->setProduct($product);

        self::assertEquals($product->getSku(), $requestProduct->getProductSku());
    }

    /**
     * @depends testProperties
     */
    public function testAddRequestProductItem(): void
    {
        $requestProduct = new RequestProduct();
        $requestProductItem = new RequestProductItem();

        self::assertNull($requestProductItem->getRequestProduct());

        $requestProduct->addRequestProductItem($requestProductItem);

        self::assertEquals($requestProduct, $requestProductItem->getRequestProduct());
    }
}
