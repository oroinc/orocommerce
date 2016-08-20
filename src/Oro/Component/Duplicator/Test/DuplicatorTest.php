<?php

namespace Oro\Component\Duplicator\Test;

use Oro\Component\Duplicator\Duplicator;
use Oro\Component\Duplicator\Filter\FilterFactory;
use Oro\Component\Duplicator\Matcher\MatcherFactory;
use Oro\Component\Duplicator\ObjectType;
use Oro\Component\Duplicator\Test\Stub\ProductUnit;
use Oro\Component\Duplicator\Test\Stub\RequestProduct;
use Oro\Component\Duplicator\Test\Stub\RequestProductItem;
use Oro\Component\Duplicator\Test\Stub\RFPRequest;
use Oro\Component\Duplicator\Test\Stub\Status;

class DuplicatorTest extends \PHPUnit_Framework_TestCase
{
    public function testDuplicate()
    {
        $now = new \DateTime();

        $params = [
            [['collection'], ['propertyType', ['Doctrine\Common\Collections\Collection']]],
            [['setNull'], ['propertyName', ['id']]],
            [['keep'], ['propertyName', ['status']]],
            [['replaceValue', $now], ['property', ['Oro\Component\Duplicator\Test\Stub\RFPRequest', 'createdAt']]],
            [['setNull'], ['property', ['Oro\Component\Duplicator\Test\Stub\RequestProduct', 'id']]],
            [['shallowCopy'], ['property', ['Oro\Component\Duplicator\Test\Stub\RequestProductItem', 'unit']]],
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
        $this->assertSame($rfpRequest->getStatus()->getTitle(), $rfpRequestCopy->getStatus()->getTitle());

        $this->assertNotSame($rfpRequestCopy->getRequestProducts(), $rfpRequest->getRequestProducts());
        $this->assertEquals($rfpRequestCopy->getRequestProducts(), $rfpRequest->getRequestProducts());

        /** @var RequestProduct $productCopy */
        $productCopy = $rfpRequestCopy->getRequestProducts()->first();
        /** @var RequestProduct $product */
        $product = $rfpRequest->getRequestProducts()->first();
        $this->assertNotSame($product, $productCopy);
        $this->assertEquals($product, $productCopy);
        $this->assertEquals($product->getComment(), $productCopy->getComment());

        /** @var RequestProductItem $productItem */
        $productItem = $product->getProductItems()->first();
        /** @var RequestProductItem $productItemCopy */
        $productItemCopy = $productCopy->getProductItems()->first();

        $this->assertEquals($productItem, $productItemCopy);
        $this->assertNotSame($productItem, $productItemCopy);

        $this->assertNotSame($productItem->getUnit(), $productItemCopy->getUnit());
        $this->assertEquals($productItem->getUnit(), $productItemCopy->getUnit());
        $this->assertEquals($productItem->getUnit()->getUnit(), $productItemCopy->getUnit()->getUnit());
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

    /**
     * @return Duplicator
     */
    protected function createDuplicator()
    {
        $duplicator = new Duplicator();
        $duplicator->setFilterFactory($this->createFilterFactory());
        $duplicator->setMatcherFactory($this->createMatcherFactory());

        return $duplicator;
    }

    /**
     * @return FilterFactory
     */
    protected function createFilterFactory()
    {
        $factory = new FilterFactory();
        $collectionFilterClass = '\DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter';
        $factory->addObjectType(new ObjectType('setNull', '\DeepCopy\Filter\SetNullFilter'))
            ->addObjectType(new ObjectType('keep', '\DeepCopy\Filter\KeepFilter'))
            ->addObjectType(new ObjectType('collection', '\DeepCopy\Filter\Doctrine\DoctrineCollectionFilter'))
            ->addObjectType(new ObjectType('emptyCollection', $collectionFilterClass))
            ->addObjectType(new ObjectType('replaceValue', '\Oro\Component\Duplicator\Filter\ReplaceValueFilter'))
            ->addObjectType(new ObjectType('shallowCopy', '\Oro\Component\Duplicator\Filter\ShallowCopyFilter'));

        return $factory;
    }

    /**
     * @return MatcherFactory
     */
    protected function createMatcherFactory()
    {
        $factory = new MatcherFactory();
        $factory->addObjectType(new ObjectType('property', '\DeepCopy\Matcher\PropertyMatcher'))
            ->addObjectType(new ObjectType('propertyName', '\DeepCopy\Matcher\PropertyNameMatcher'))
            ->addObjectType(new ObjectType('propertyType', '\DeepCopy\Matcher\PropertyTypeMatcher'));

        return $factory;
    }
}
