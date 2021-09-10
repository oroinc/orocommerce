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
    private Request|\PHPUnit\Framework\MockObject\MockObject $request;

    private RequestProductHandler|\PHPUnit\Framework\MockObject\MockObject $requestProductHandler;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->requestProductHandler = new RequestProductHandler($requestStack);
    }

    /**
     * @dataProvider idDataProvider
     */
    public function testGetCategoryId(string|int|bool|null $value, int $expected): void
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::CATEGORY_ID_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getCategoryId();
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider idDataProvider
     */
    public function testGetContentVariantId(string|int|bool|null $value, int $expected): void
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(CategoryPageContentVariantType::CATEGORY_CONTENT_VARIANT_ID_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getCategoryContentVariantId();
        self::assertSame($expected, $actual);
    }

    public function testGetCategoryIdWithoutRequest(): void
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        self::assertSame(0, $requestProductHandler->getCategoryId());
    }

    public function testGetContentVariantIdWithoutRequest(): void
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        self::assertSame(0, $requestProductHandler->getCategoryContentVariantId());
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
    public function testGetIncludeSubcategoriesChoice(string|int|bool|null $value, bool $expected): void
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY, false)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getIncludeSubcategoriesChoice();
        self::assertEquals($expected, $actual);
    }

    public function getIncludeSubcategoriesChoiceDataProvider(): array
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

    /**
     * @dataProvider getIncludeNotCategorizedProductsChoiceDataProvider
     */
    public function testGetIncludeNotCategorizedProductsChoice($value, $expected): void
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getIncludeNotCategorizedProductsChoice();
        self::assertEquals($expected, $actual);
    }

    public function getIncludeNotCategorizedProductsChoiceDataProvider(): array
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
     */
    public function testGetOverrideVariantConfiguration(string|int|bool|null $value, bool $expected): void
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(CategoryPageContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getOverrideVariantConfiguration();
        self::assertEquals($expected, $actual);
    }

    public function getOverrideVariantConfigurationDataProvider(): array
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

    public function testGetIncludeSubcategoriesChoiceWithEmptyRequest(): void
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        self::assertEquals(
            RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE,
            $requestProductHandler->getIncludeSubcategoriesChoice()
        );
    }

    public function testGetIncludeNotCategorizedProductsChoiceWithEmptyRequest(): void
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        self::assertEquals(
            RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE,
            $requestProductHandler->getIncludeNotCategorizedProductsChoice()
        );
    }

    public function testGetOverrideVariantConfigurationWithEmptyRequest(): void
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        self::assertFalse($requestProductHandler->getOverrideVariantConfiguration());
    }
}
