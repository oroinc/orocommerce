<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;

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

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->requestProductHandler = new RequestProductHandler($requestStack);
    }

    /**
     * @dataProvider getCategoryIdDataProvider
     *
     * @param $value
     * @param $expected
     */
    public function testGetCategoryId($value, $expected)
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::CATEGORY_ID_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getCategoryId();
        $this->assertEquals($expected, $actual);
    }

    public function testGetCategoryIdWithoutRequest()
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        $this->assertFalse($requestProductHandler->getCategoryId());
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
                'expected' => RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE,
            ],
        ];
    }

    public function testGetIncludeSubcategoriesChoiceWithEmptyRequest()
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        $this->assertEquals(
            RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE,
            $requestProductHandler->getIncludeSubcategoriesChoice()
        );
    }
}
