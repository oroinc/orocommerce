<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Symfony\Component\HttpFoundation\Request;

class RequestProductHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var  RequestProductHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestProductHandler;

    public function setUp()
    {
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->requestProductHandler = new RequestProductHandler();
    }

    public function testGetCategoryId()
    {
        $id = 1;
        $this->requestProductHandler->setRequest($this->request);
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::CATEGORY_ID_KEY)
            ->willReturn($id);
        $result = $this->requestProductHandler->getCategoryId();
        $this->assertEquals($id, $result);
    }

    public function testGetCategoryIdWithoutRequest()
    {
        $this->requestProductHandler->setRequest(null);
        $this->assertFalse($this->requestProductHandler->getCategoryId());
    }
}
