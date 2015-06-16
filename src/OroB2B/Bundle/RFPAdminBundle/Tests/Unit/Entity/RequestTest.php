<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Entity;

use OroB2B\Bundle\RFPBundle\Tests\Unit\Entity\RequestStatusTestCase;

use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;

class RequestTest extends RequestStatusTestCase
{
    public function testConstruct()
    {
        $request = new Request();

        $now = new \DateTime();

        $this->assertInstanceOf('DateTime', $request->getCreatedAt());
        $this->assertLessThanOrEqual($now, $request->getCreatedAt());

        $this->assertInstanceOf('DateTime', $request->getUpdatedAt());
        $this->assertLessThanOrEqual($now, $request->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $request = new Request();
        $request->preUpdate();

        $this->assertInstanceOf('DateTime', $request->getUpdatedAt());
        $this->assertLessThanOrEqual(new \DateTime(), $request->getUpdatedAt());
    }

    public function testAddRequestProduct()
    {
        $request        = new Request();
        $requestProduct = new RequestProduct();

        $this->assertNull($requestProduct->getRequest());

        $request->addRequestProduct($requestProduct);

        $this->assertEquals($request, $requestProduct->getRequest());
    }
}
