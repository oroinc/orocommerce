<?php

namespace OroB2B\Component\Duplicator\Test;

use OroB2B\Component\Duplicator\Duplicator;
use OroB2B\Component\Duplicator\Test\Stub\ProductUnit;
use OroB2B\Component\Duplicator\Test\Stub\RequestProduct;
use OroB2B\Component\Duplicator\Test\Stub\RequestProductItem;
use OroB2B\Component\Duplicator\Test\Stub\RFPRequest;
use OroB2B\Component\Duplicator\Test\Stub\Status;

class DuplicatorTest extends \PHPUnit_Framework_TestCase
{
    public function testDuplicate()
    {
        $now = new \DateTime();

        $params = [
            [['collection'], ['propertyType', 'Doctrine\Common\Collections\Collection']],

            [['setNull'], ['propertyName', 'id']],
            [['keep'], ['propertyName', 'status']],
            [['replace', $now], ['property', [RFPRequest::class, 'createdAt']]],

            [['setNull'], ['property', [RequestProduct::class, 'id']]],
            [['keep'], ['property', [RequestProduct::class, 'product']]],


            [['keep'], ['property', [RequestProductItem::class, 'unit']]],
        ];
        $rfpRequest = $this->getRFP();

        $duplicator = new Duplicator();
        /** @var RFPRequest $rfpRequestCopy */
        $rfpRequestCopy = $duplicator->duplicate($rfpRequest, $params);

        $this->assertNotSame($rfpRequest, $rfpRequestCopy);
        $this->assertSame($rfpRequestCopy->getCreatedAt(), $now);
        $this->assertEquals($rfpRequestCopy->getId(), null);
        $this->assertEquals($rfpRequest->getEmail(), $rfpRequestCopy->getEmail());
        $this->assertSame($rfpRequest->getStatus(), $rfpRequestCopy->getStatus());

        /** @var RequestProduct $productCopy */
        $productCopy = $rfpRequestCopy->getRequestProducts()->first();
        /** @var RequestProduct $product */
        $product = $rfpRequest->getRequestProducts()->first();
        $this->assertNotSame($product, $productCopy);
        $this->assertEquals($product, $productCopy);

        /** @var RequestProductItem $productItem */
        $productItem = $product->getProductItems()->first();
        /** @var RequestProductItem $productItemCopy */
        $productItemCopy = $productCopy->getProductItems()->first();

        $this->assertEquals($productItem, $productItemCopy);
        $this->assertNotSame($productItem, $productItemCopy);
        $this->assertSame($productItem->getUnit(), $productItemCopy->getUnit());
    }

    /**
     * @return RFPRequest
     */
    protected function getRFP()
    {
        $status = new Status();
        $status->setTitle('open');
        $requestProduct = $this->getRequestProduct();

        $request = new RFPRequest(1);
        $request->setEmail('test@test.com');

        $request->addRequestProduct($requestProduct);
        $request->setStatus($status);

        return $request;
    }

    /**
     * @return RequestProduct
     */
    protected function getRequestProduct()
    {
        $unit = new ProductUnit();
        $unit->setUnit('USD');

        $item = new RequestProductItem();
        $item->setUnit($unit);

        $requestProduct = new RequestProduct();
        $requestProduct->setComment('Product comment');
        $requestProduct->addRequestProductItem($item);

        return $requestProduct;
    }
}
