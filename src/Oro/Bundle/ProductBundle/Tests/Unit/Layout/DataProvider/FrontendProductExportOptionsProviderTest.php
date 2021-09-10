<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\ProductBundle\Handler\RequestContentVariantHandler;
use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Oro\Bundle\ProductBundle\Layout\DataProvider\FrontendProductExportOptionsProvider;

class FrontendProductExportOptionsProviderTest extends \PHPUnit\Framework\TestCase
{
    private FrontendProductExportOptionsProvider $productExportOptionsProvider;

    /** @var RequestProductHandler|\PHPUnit\Framework\MockObject\MockObject */
    private RequestProductHandler $requestProductHandler;

    /** @var SearchProductHandler|\PHPUnit\Framework\MockObject\MockObject  */
    private SearchProductHandler $searchProductHandler;

    /** @var RequestContentVariantHandler|\PHPUnit\Framework\MockObject\MockObject  */
    private RequestContentVariantHandler $requestHandler;

    protected function setUp(): void
    {
        $this->requestProductHandler = $this->createMock(RequestProductHandler::class);
        $this->searchProductHandler = $this->createMock(SearchProductHandler::class);
        $this->requestHandler = $this->createMock(RequestContentVariantHandler::class);

        $this->productExportOptionsProvider = new FrontendProductExportOptionsProvider(
            $this->requestProductHandler,
            $this->searchProductHandler,
            $this->requestHandler
        );
    }

    /**
     * @dataProvider getDefaultGridExportRequestOptionsDataProviderWithCategoryId
     * @param int|null $categoryId
     * @param bool|null $includeSubcategories
     * @param int $categoryContentId
     * @param bool $overrideConfiguration
     * @param string|null $searchString
     * @param string|null $expected
     */
    public function testGetDefaultGridExportRequestOptionsWithCategoryId(
        ?int $categoryId,
        bool $includeSubcategories,
        int $categoryContentId,
        bool $overrideConfiguration,
        ?string $searchString,
        ?string $expected
    ) {
        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn($categoryId);

        $this->requestProductHandler->expects($this->once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn($includeSubcategories);

        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryContentVariantId')
            ->willReturn($categoryContentId);

        $this->requestProductHandler->expects($this->once())
            ->method('getOverrideVariantConfiguration')
            ->willReturn($overrideConfiguration);

        $this->searchProductHandler->expects($this->once())
            ->method('getSearchString')
            ->willReturn($searchString);

        $this->requestHandler->expects($this->once())
            ->method('getContentVariantId')
            ->willReturn(false);

        $this->requestHandler->expects($this->never())
            ->method('getOverrideVariantConfiguration');

        $optionsString = $this->productExportOptionsProvider->getDefaultGridExportRequestOptions();

        $this->assertEquals($expected, $optionsString);
    }

    public function getDefaultGridExportRequestOptionsDataProviderWithCategoryId()
    {
        return [
            [
                'categoryId' => 2,
                'includeSubcategories' => false,
                'categoryContentId' => 5,
                'overrideConfiguration' => false,
                'searchString' => null,
                'expected' => 'g%5BcategoryId%5D=2&g%5BincludeSubcategories%5D=0&g%5BcategoryContentVariantId%5D=5&'
                    . 'g%5BoverrideVariantConfiguration%5D=0'
            ],
            [
                'categoryId' => 3,
                'includeSubcategories' => true,
                'categoryContentId' => 5,
                'overrideConfiguration' => true,
                'searchString' => null,
                'expected' => 'g%5BcategoryId%5D=3&g%5BincludeSubcategories%5D=1&g%5BcategoryContentVariantId%5D=5&'
                    . 'g%5BoverrideVariantConfiguration%5D=1'
            ],
            [
                'categoryId' => 3,
                'includeSubcategories' => false,
                'categoryContentId' => 5,
                'overrideConfiguration' => true,
                'searchString' => null,
                'expected' => 'g%5BcategoryId%5D=3&g%5BincludeSubcategories%5D=0&g%5BcategoryContentVariantId%5D=5&'
                    . 'g%5BoverrideVariantConfiguration%5D=1'
            ],
            [
                'categoryId' => 3,
                'includeSubcategories' => true,
                'categoryContentId' => 5,
                'overrideConfiguration' => true,
                'searchString' => 'Product',
                'expected' => 'g%5BcategoryId%5D=3&g%5BincludeSubcategories%5D=1&g%5BcategoryContentVariantId%5D=5&'
                    . 'g%5BoverrideVariantConfiguration%5D=1&g%5Bsearch%5D=Product'
            ]
        ];
    }

    /**
     * @dataProvider getDefaultGridExportRequestOptionsWithoutCategoryIdDataProvider
     */
    public function testGetDefaultGridExportRequestOptionsWithoutCategoryId(
        ?string $searchString,
        ?int $contentVariantId,
        bool $overrideVariantConfiguration,
        ?string $expected
    ) {
        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(0);

        $this->requestProductHandler->expects($this->never())
            ->method('getIncludeSubcategoriesChoice');

        $this->requestProductHandler->expects($this->never())
            ->method('getCategoryContentVariantId');

        $this->requestProductHandler->expects($this->never())
            ->method('getOverrideVariantConfiguration');

        $this->searchProductHandler->expects($this->once())
            ->method('getSearchString')
            ->willReturn($searchString);

        $this->requestHandler->expects($this->once())
            ->method('getContentVariantId')
            ->willReturn($contentVariantId);

        $this->requestHandler->expects($this->once())
            ->method('getOverrideVariantConfiguration')
            ->willReturn($overrideVariantConfiguration);

        $optionsString = $this->productExportOptionsProvider->getDefaultGridExportRequestOptions();

        $this->assertEquals($expected, $optionsString);
    }

    public function getDefaultGridExportRequestOptionsWithoutCategoryIdDataProvider(): array
    {
        return [
            [
                'searchString' => 'Product',
                'contentVariantId' => 2,
                'overrideVariantConfiguration' => true,
                'expected' => 'g%5Bsearch%5D=Product&g%5BcontentVariantId%5D=2&g%5BoverrideVariantConfiguration%5D=1'
            ],
            [
                'searchString' => 'Product',
                'contentVariantId' => 2,
                'overrideVariantConfiguration' => false,
                'expected' => 'g%5Bsearch%5D=Product&g%5BcontentVariantId%5D=2&g%5BoverrideVariantConfiguration%5D=0'
            ],
            [
                'searchString' => null,
                'contentVariantId' => 2,
                'overrideVariantConfiguration' => true,
                'expected' => 'g%5BcontentVariantId%5D=2&g%5BoverrideVariantConfiguration%5D=1'
            ]
        ];
    }

    /**
     * @dataProvider testGetDefaultGridExportRequestOptionsWithSearchOnlyDataProvider
     */
    public function testGetDefaultGridExportRequestOptionsWithSearchOnly(
        ?string $searchString,
        ?string $expected
    ) {
        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(0);

        $this->requestProductHandler->expects($this->never())
            ->method('getIncludeSubcategoriesChoice');

        $this->requestProductHandler->expects($this->never())
            ->method('getCategoryContentVariantId');

        $this->requestProductHandler->expects($this->never())
            ->method('getOverrideVariantConfiguration');

        $this->searchProductHandler->expects($this->once())
            ->method('getSearchString')
            ->willReturn($searchString);

        $this->requestHandler->expects($this->once())
            ->method('getContentVariantId')
            ->willReturn(false);

        $this->requestHandler->expects($this->never())
            ->method('getOverrideVariantConfiguration');

        $optionsString = $this->productExportOptionsProvider->getDefaultGridExportRequestOptions();

        $this->assertEquals($expected, $optionsString);
    }

    public function testGetDefaultGridExportRequestOptionsWithSearchOnlyDataProvider(): array
    {
        return [
            [
                'searchString' => 'Product',
                'expected' => 'g%5Bsearch%5D=Product'
            ],
            [
                'searchString' => null,
                'expected' => null
            ]
        ];
    }
}
