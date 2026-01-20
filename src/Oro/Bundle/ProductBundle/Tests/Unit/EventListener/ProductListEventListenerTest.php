<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductListEventListener;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\UIBundle\Tools\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductListEventListenerTest extends TestCase
{
    private ImagePlaceholderProviderInterface&MockObject $imagePlaceholderProvider;
    private WebpConfiguration&MockObject $webpConfiguration;
    private ProductListEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);

        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->expects(self::any())
            ->method('getAbsolutePath')
            ->willReturnCallback(static fn (string $path) => '/absolute' . $path);

        $apiUrlResolver = $this->createMock(ApiUrlResolver::class);
        $apiUrlResolver
            ->expects(self::any())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(false);

        $this->listener = new ProductListEventListener(
            $this->imagePlaceholderProvider,
            $this->webpConfiguration,
            $urlHelper,
            $apiUrlResolver
        );
    }

    public function testOnBuildQuery(): void
    {
        $query = $this->createMock(SearchQueryInterface::class);

        $query->expects(self::exactly(8))
            ->method('addSelect')
            ->withConsecutive(
                ['text.type'],
                ['text.sku'],
                ['text.names_LOCALIZATION_ID as name'],
                ['text.image_product_large as image'],
                ['text.primary_unit as unit'],
                ['text.product_units'],
                ['integer.newArrival'],
                ['integer.variant_fields_count']
            )
            ->willReturnSelf();

        $this->listener->onBuildQuery(new BuildQueryProductListEvent('test_list', $query));
    }

    public function testOnBuildQueryWebpSupported(): void
    {
        $query = $this->createMock(SearchQueryInterface::class);

        $query->expects(self::exactly(9))
            ->method('addSelect')
            ->withConsecutive(
                ['text.type'],
                ['text.sku'],
                ['text.names_LOCALIZATION_ID as name'],
                ['text.image_product_large as image'],
                ['text.primary_unit as unit'],
                ['text.product_units'],
                ['integer.newArrival'],
                ['integer.variant_fields_count'],
                ['text.image_product_large_webp as imageWebp']
            )
            ->willReturnSelf();

        $this->webpConfiguration->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(true);

        $this->listener->onBuildQuery(new BuildQueryProductListEvent('test_list', $query));
    }

    public function testOnBuildResult(): void
    {
        $noImagePath = '/path/no_image.jpg';
        $productData = [
            1 => [
                'id'                   => 1,
                'type'                 => Product::TYPE_CONFIGURABLE,
                'sku'                  => 'p1',
                'name'                 => 'product 1',
                'image'                => '/image/1/medium',
                'unit'                 => 'items',
                'product_units'        => json_encode(['items' => 0, 'set' => 2]),
                'newArrival'           => 1,
                'variant_fields_count' => 3
            ],
            2 => [
                'id'                   => 2,
                'type'                 => Product::TYPE_SIMPLE,
                'sku'                  => 'p2',
                'name'                 => 'product 2',
                'image'                => '',
                'unit'                 => 'items',
                'product_units'        => '',
                'newArrival'           => 0,
                'variant_fields_count' => ''
            ]
        ];
        $productView1 = $this->createMock(ProductView::class);
        $productView2 = $this->createMock(ProductView::class);
        $productViews = [1 => $productView1, 2 => $productView2];

        $productView1->expects(self::exactly(9))
            ->method('set')
            ->withConsecutive(
                ['type', self::identicalTo(Product::TYPE_CONFIGURABLE)],
                ['sku', self::identicalTo('p1')],
                ['name', self::identicalTo('product 1')],
                ['hasImage', self::identicalTo(true)],
                ['image', self::identicalTo('/absolute/image/1/medium')],
                ['unit', self::identicalTo('items')],
                ['product_units', self::identicalTo(['items' => 0, 'set' => 2])],
                ['newArrival', self::identicalTo(true)],
                ['variant_fields_count', self::identicalTo(3)]
            );
        $productView2->expects(self::exactly(9))
            ->method('set')
            ->withConsecutive(
                ['type', self::identicalTo(Product::TYPE_SIMPLE)],
                ['sku', self::identicalTo('p2')],
                ['name', self::identicalTo('product 2')],
                ['hasImage', self::identicalTo(false)],
                ['image', self::identicalTo($noImagePath)],
                ['unit', self::identicalTo('items')],
                ['product_units', self::identicalTo([])],
                ['newArrival', self::identicalTo(false)],
                ['variant_fields_count', self::identicalTo(null)]
            );

        $this->imagePlaceholderProvider->expects(self::once())
            ->method('getPath')
            ->with('product_large')
            ->willReturn($noImagePath);

        $this->listener->onBuildResult(new BuildResultProductListEvent('test_list', $productData, $productViews));
    }

    public function testOnBuildResultWebpSupported(): void
    {
        $productData = [
            1 => [
                'id'                   => 1,
                'type'                 => Product::TYPE_CONFIGURABLE,
                'sku'                  => 'p1',
                'name'                 => 'product 1',
                'image'                => '/image/1/medium',
                'imageWebp'            => '/image/1/medium/webp',
                'unit'                 => 'items',
                'product_units'        => json_encode(['items' => 0, 'set' => 2]),
                'newArrival'           => 1,
                'variant_fields_count' => 3
            ],
        ];
        $productView1 = $this->createMock(ProductView::class);
        $productViews = [1 => $productView1];

        $productView1->expects(self::exactly(10))
            ->method('set')
            ->withConsecutive(
                ['type', self::identicalTo(Product::TYPE_CONFIGURABLE)],
                ['sku', self::identicalTo('p1')],
                ['name', self::identicalTo('product 1')],
                ['hasImage', self::identicalTo(true)],
                ['image', self::identicalTo('/absolute/image/1/medium')],
                ['imageWebp', self::identicalTo('/absolute/image/1/medium/webp')],
            );

        $this->listener->onBuildResult(new BuildResultProductListEvent('test_list', $productData, $productViews));
    }

    public function testOnBuildResultWithAbsoluteUrls(): void
    {
        $noImagePath = '/path/no_image.jpg';
        $productData = [
            1 => [
                'id'                   => 1,
                'type'                 => Product::TYPE_CONFIGURABLE,
                'sku'                  => 'p1',
                'name'                 => 'product 1',
                'image'                => '/image/1/medium',
                'unit'                 => 'items',
                'product_units'        => json_encode(['items' => 0, 'set' => 2]),
                'newArrival'           => 1,
                'variant_fields_count' => 3
            ],
            2 => [
                'id'                   => 2,
                'type'                 => Product::TYPE_SIMPLE,
                'sku'                  => 'p2',
                'name'                 => 'product 2',
                'image'                => '',
                'unit'                 => 'items',
                'product_units'        => '',
                'newArrival'           => 0,
                'variant_fields_count' => ''
            ]
        ];
        $productView1 = $this->createMock(ProductView::class);
        $productView2 = $this->createMock(ProductView::class);
        $productViews = [1 => $productView1, 2 => $productView2];

        $productView1->expects(self::exactly(9))
            ->method('set')
            ->withConsecutive(
                ['type', self::identicalTo(Product::TYPE_CONFIGURABLE)],
                ['sku', self::identicalTo('p1')],
                ['name', self::identicalTo('product 1')],
                ['hasImage', self::identicalTo(true)],
                ['image', self::identicalTo('https://example.com/image/1/medium')],
                ['unit', self::identicalTo('items')],
                ['product_units', self::identicalTo(['items' => 0, 'set' => 2])],
                ['newArrival', self::identicalTo(true)],
                ['variant_fields_count', self::identicalTo(3)]
            );
        $productView2->expects(self::exactly(9))
            ->method('set')
            ->withConsecutive(
                ['type', self::identicalTo(Product::TYPE_SIMPLE)],
                ['sku', self::identicalTo('p2')],
                ['name', self::identicalTo('product 2')],
                ['hasImage', self::identicalTo(false)],
                ['image', self::identicalTo($noImagePath)],
                ['unit', self::identicalTo('items')],
                ['product_units', self::identicalTo([])],
                ['newArrival', self::identicalTo(false)],
                ['variant_fields_count', self::identicalTo(null)]
            );

        $this->imagePlaceholderProvider->expects(self::once())
            ->method('getPath')
            ->with('product_large')
            ->willReturn($noImagePath);

        // Create listener with absolute URLs enabled
        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->expects(self::any())
            ->method('getAbsoluteUrl')
            ->willReturnCallback(static fn (string $path) => 'https://example.com' . $path);

        $apiUrlResolver = $this->createMock(ApiUrlResolver::class);
        $apiUrlResolver
            ->expects(self::any())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(true);

        $listener = new ProductListEventListener(
            $this->imagePlaceholderProvider,
            $this->webpConfiguration,
            $urlHelper,
            $apiUrlResolver
        );

        $listener->onBuildResult(new BuildResultProductListEvent('test_list', $productData, $productViews));
    }

    public function testOnBuildResultWebpSupportedWithAbsoluteUrls(): void
    {
        $productData = [
            1 => [
                'id'                   => 1,
                'type'                 => Product::TYPE_CONFIGURABLE,
                'sku'                  => 'p1',
                'name'                 => 'product 1',
                'image'                => '/image/1/medium',
                'imageWebp'            => '/image/1/medium/webp',
                'unit'                 => 'items',
                'product_units'        => json_encode(['items' => 0, 'set' => 2]),
                'newArrival'           => 1,
                'variant_fields_count' => 3
            ],
        ];
        $productView1 = $this->createMock(ProductView::class);
        $productViews = [1 => $productView1];

        $productView1->expects(self::exactly(10))
            ->method('set')
            ->withConsecutive(
                ['type', self::identicalTo(Product::TYPE_CONFIGURABLE)],
                ['sku', self::identicalTo('p1')],
                ['name', self::identicalTo('product 1')],
                ['hasImage', self::identicalTo(true)],
                ['image', self::identicalTo('https://example.com/image/1/medium')],
                ['imageWebp', self::identicalTo('https://example.com/image/1/medium/webp')],
            );

        // Create listener with absolute URLs enabled
        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->expects(self::any())
            ->method('getAbsoluteUrl')
            ->willReturnCallback(static fn (string $path) => 'https://example.com' . $path);

        $apiUrlResolver = $this->createMock(ApiUrlResolver::class);
        $apiUrlResolver
            ->expects(self::any())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(true);

        $listener = new ProductListEventListener(
            $this->imagePlaceholderProvider,
            $this->webpConfiguration,
            $urlHelper,
            $apiUrlResolver
        );

        $listener->onBuildResult(new BuildResultProductListEvent('test_list', $productData, $productViews));
    }
}
