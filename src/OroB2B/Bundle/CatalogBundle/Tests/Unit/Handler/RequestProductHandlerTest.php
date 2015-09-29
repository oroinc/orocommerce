<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Handler;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;

class RequestProductHandlerTest extends \PHPUnit_Framework_TestCase
{
    const ROOT_CATEGORY_ID = 1;

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
        $actual = $this->requestProductHandler->getCategoryId();
        $this->assertEquals($expected, $actual);
    }

    public function testGetCategoryIdWithoutRequest()
    {
        $this->requestProductHandler->setRequest(null);
        $this->assertFalse($this->requestProductHandler->getCategoryId());
    }

    /**
     * @return array
     */
    public function getCategoryIdDataProvider()
    {
        return [
            [
                'value' => null,
                'expected' => false,
            ],
            [
                'value' => false,
                'expected' => false,
            ],
            [
                'value' => true,
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
            [
                'value' => -1,
                'expected' => false,
            ],
            [
                'value' => '1',
                'expected' => true,
            ],
            [
                'value' => '0',
                'expected' => false,
            ],
            [
                'value' => '-1',
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider getIncludeSubcategoriesChoiceDataProvider
     *
     * @param $value
     * @param $expected
     */
    public function testGetIncludeSubcategoriesChoice($value, $expected)
    {
        $this->requestProductHandler->setRequest($this->request);
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getIncludeSubcategoriesChoice();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getIncludeSubcategoriesChoiceDataProvider()
    {
        return [
            [
                'value' => true,
                'expected' => true,
            ],
            [
                'value' => false,
                'expected' => false,
            ],
            [
                'value' => 'true',
                'expected' => true,
            ],
            [
                'value' => 'false',
                'expected' => false,
            ],
            [
                'value' => 1,
                'expected' => true,
            ],
            [
                'value' => 0,
                'expected' => false,
            ],
            [
                'value' => -1,
                'expected' => RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE,
            ],
            [
                'value' => '1',
                'expected' => true,
            ],
            [
                'value' => '0',
                'expected' => false,
            ],
            [
                'value' => '-1',
                'expected' => RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE,
            ],
            [
                'value' => null,
                'expected' => false,
            ],
            [
                'value' => 'test',
                'expected' => RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE
            ],
        ];
    }

    public function testGetIncludeSubcategoriesChoiceWithEmptyRequest()
    {
        $this->requestProductHandler->setRequest(null);
        $this->assertEquals(
            RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE,
            $this->requestProductHandler->getIncludeSubcategoriesChoice()
        );
    }
}
