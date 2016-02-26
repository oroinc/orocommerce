<?php

namespace OroB2B\Component\Duplicator\Test;

use OroB2B\Component\Duplicator\Duplicator;
use OroB2B\Component\Duplicator\Filter\FilterFactory;
use OroB2B\Component\Duplicator\Matcher\MatcherFactory;
use OroB2B\Component\Duplicator\ObjectType;
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
            [['collection'], ['propertyType', ['Doctrine\Common\Collections\Collection']]],

            [['setNull'], ['propertyName', ['id']]],
            [['keep'], ['propertyName', ['status']]],
            [['replaceValue', $now], ['property', ['OroB2B\Component\Duplicator\Test\Stub\RFPRequest', 'createdAt']]],

            [['setNull'], ['property', ['OroB2B\Component\Duplicator\Test\Stub\RequestProduct', 'id']]],
            [['keep'], ['property', ['OroB2B\Component\Duplicator\Test\Stub\RequestProduct', 'product']]],

            [['keep'], ['property', ['OroB2B\Component\Duplicator\Test\Stub\RequestProductItem', 'unit']]],
        ];
        $rfpRequest = $this->getRFP();

        $duplicator = $this->createDuplicator();
        /** @var RFPRequest $rfpRequestCopy */
        $rfpRequestCopy = $duplicator->duplicate($rfpRequest, $params);

        $this->assertNotSame($rfpRequest, $rfpRequestCopy);
        $this->assertSame($rfpRequestCopy->getCreatedAt(), $now);
        $this->assertEquals($rfpRequestCopy->getId(), null);
        $this->assertEquals($rfpRequest->getEmail(), $rfpRequestCopy->getEmail());
        $this->assertSame($rfpRequest->getStatus(), $rfpRequestCopy->getStatus());

        $this->assertNotSame($rfpRequestCopy->getRequestProducts(),  $rfpRequest->getRequestProducts());
        $this->assertEquals($rfpRequestCopy->getRequestProducts(),  $rfpRequest->getRequestProducts());

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

    protected function createDuplicator()
    {
        $duplicator = new Duplicator();
        $duplicator->setFilterFactory($this->createFilterFactory());
        $duplicator->setMatcherFactory($this->createMatcherFactory());

        return $duplicator;
    }

    protected function createFilterFactory()
    {
        $factory = new FilterFactory();
        $factory->addObjectType(new ObjectType('setNull', '\DeepCopy\Filter\SetNullFilter'))
            ->addObjectType(new ObjectType('keep', '\DeepCopy\Filter\KeepFilter'))
            ->addObjectType(new ObjectType('collection', '\DeepCopy\Filter\Doctrine\DoctrineCollectionFilter'))
            ->addObjectType(new ObjectType('emptyCollection', '\DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter'))
            ->addObjectType(new ObjectType('replaceValue', '\OroB2B\Component\Duplicator\Filter\ReplaceValueFilter'))
            ->addObjectType(new ObjectType('shallowCopy', '\OroB2B\Component\Duplicator\Filter\ShallowCopyFilter'));

        return $factory;
    }

    protected function createMatcherFactory()
    {
        $factory = new MatcherFactory();
        $factory->addObjectType(new ObjectType('property', '\DeepCopy\Matcher\PropertyMatcher'))
            ->addObjectType(new ObjectType('propertyName', '\DeepCopy\Matcher\PropertyNameMatcher'))
            ->addObjectType(new ObjectType('propertyType', '\DeepCopy\Matcher\PropertyTypeMatcher'));

        return $factory;
    }
}
