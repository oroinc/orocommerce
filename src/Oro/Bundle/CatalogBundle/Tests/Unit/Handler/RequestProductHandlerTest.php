<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Handler;

use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RequestProductHandlerTest extends \PHPUnit\Framework\TestCase
{
    const ROOT_CATEGORY_ID = 1;

    /** @var  Request|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var  RequestProductHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestProductHandler;

    protected function setUp(): void
    {
        $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');

        /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->requestProductHandler = new RequestProductHandler($requestStack);
    }

    /**
     * @dataProvider idDataProvider
     *
     * @param string|int|bool|null $value
     * @param int $expected
     */
    public function testGetCategoryId($value, int $expected): void
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::CATEGORY_ID_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getCategoryId();
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider idDataProvider
     *
     * @param string|int|bool|null $value
     * @param int $expected
     */
    public function testGetContentVariantId($value, int $expected): void
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(CategoryPageContentVariantType::CATEGORY_CONTENT_VARIANT_ID_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getCategoryContentVariantId();
        $this->assertSame($expected, $actual);
    }

    public function testGetCategoryIdWithoutRequest(): void
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        $this->assertSame(0, $requestProductHandler->getCategoryId());
    }

    public function testGetContentVariantIdWithoutRequest(): void
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        $this->assertSame(0, $requestProductHandler->getCategoryContentVariantId());
    }

    public function idDataProvider(): array
    {
        return [
            [
                'value' => null,
                'expected' => 0,
            ],
            [
                'value' => false,
                'expected' => 0,
            ],
            [
                'value' => true,
                'expected' => 0,
            ],
            [
                'value' => 'true',
                'expected' => 0,
            ],
            [
                'value' => 'false',
                'expected' => 0,
            ],
            [
                'value' => 1,
                'expected' => 1,
            ],
            [
                'value' => 0,
                'expected' => 0,
            ],
            [
                'value' => -1,
                'expected' => 0,
            ],
            [
                'value' => '1',
                'expected' => 1,
            ],
            [
                'value' => '0',
                'expected' => 0,
            ],
            [
                'value' => '-1',
                'expected' => 0,
            ],
        ];
    }

    /**
     * @dataProvider getIncludeSubcategoriesChoiceDataProvider
     */
    public function testGetIncludeSubcategoriesChoice($value, $expected)
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY, false)
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

    public function testGetIncludeSubcategoriesChoiceWithTrueOption()
    {
        // string value is used only for showing correct value passing
        $trueValue = 'trueValue';

        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY, $trueValue)
            ->willReturn(true);
        $actual = $this->requestProductHandler->getIncludeSubcategoriesChoice($trueValue);
        $this->assertTrue($actual);
    }

    /**
     * @dataProvider getIncludeNotCategorizedProductsChoiceDataProvider
     */
    public function testGetIncludeNotCategorizedProductsChoice($value, $expected)
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getIncludeNotCategorizedProductsChoice();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getIncludeNotCategorizedProductsChoiceDataProvider()
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
                'expected' => RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE,
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
                'expected' => RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE,
            ],
            [
                'value' => null,
                'expected' => false,
            ],
            [
                'value' => 'test',
                'expected' => RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE,
            ],
        ];
    }

    /**
     * @dataProvider getOverrideVariantConfigurationDataProvider
     *
     * @param string|int|bool $value
     * @param bool $expected
     */
    public function testGetOverrideVariantConfiguration($value, $expected)
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(CategoryPageContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getOverrideVariantConfiguration();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getOverrideVariantConfigurationDataProvider()
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
            [
                'value' => null,
                'expected' => false,
            ],
            [
                'value' => 'test',
                'expected' => false,
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

    public function testGetIncludeNotCategorizedProductsChoiceWithEmptyRequest()
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        $this->assertEquals(
            RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE,
            $requestProductHandler->getIncludeNotCategorizedProductsChoice()
        );
    }

    public function testGetOverrideVariantConfigurationWithEmptyRequest()
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        $this->assertFalse($requestProductHandler->getOverrideVariantConfiguration());
    }
}
