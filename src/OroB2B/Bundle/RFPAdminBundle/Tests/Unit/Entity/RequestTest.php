<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Entity;

use OroB2B\Bundle\RFPBundle\Tests\Unit\Entity\RequestStatusTestCase;

use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;

class RequestTest extends RequestStatusTestCase
{
    public function testAccessors()
    {
        $properties = [
            ['status', new RequestStatus()],
        ];

        static::assertPropertyAccessors(new Request(), $properties);

        static::assertPropertyCollections(new Request(), [
            ['requestProducts', new RequestProduct()],
        ]);
    }

    /**
     * @depends testAccessors
     */
    public function testConstruct()
    {
        $request = new Request();

        $now = new \DateTime();

        $this->assertInstanceOf('DateTime', $request->getCreatedAt());
        $this->assertLessThanOrEqual($now, $request->getCreatedAt());

        $this->assertInstanceOf('DateTime', $request->getUpdatedAt());
        $this->assertLessThanOrEqual($now, $request->getUpdatedAt());
    }

    /**
     * @depends testAccessors
     */
    public function testAddRequestProduct()
    {
        $request        = new Request();
        $requestProduct = new RequestProduct();

        $this->assertNull($requestProduct->getRequest());

        $request->addRequestProduct($requestProduct);

        $this->assertEquals($request, $requestProduct->getRequest());
    }
}
