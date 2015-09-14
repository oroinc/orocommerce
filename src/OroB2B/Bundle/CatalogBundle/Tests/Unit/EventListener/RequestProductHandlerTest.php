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

    /**
     * @dataProvider getCategoryIdDataProvider
     *
     * @param $value
     * @param $expected
     */
    public function testGetCategoryId($value, $expected)
    {
        $this->requestProductHandler->setRequest($this->request);
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::CATEGORY_ID_KEY)
            ->willReturn($value);
        $result = $this->requestProductHandler->getCategoryId();
        $this->assertEquals($result, $expected);
    }

    public function testGetCategoryIdWithoutRequest()
    {
        $this->requestProductHandler->setRequest(null);
        $this->assertFalse($this->requestProductHandler->getCategoryId());
    }


    public function getCategoryIdDataProvider()
    {
        return [
            [
                'value' => null,
                'expected' => false,
            ],
            [
                'value' => '1',
                'expected' => 1,
            ],
            [
                'value' => false,
                'expected' => false,
            ],
            [
                'value' => 'true',
                'expected' => false,
            ],
            [
                'value' => 'false',
                'expected' => false,
            ],
            [
                'value' => 1,
                'expected' => 1,
            ],
            [
                'value' => 0,
                'expected' => false,
            ],
        ];
    }
}
