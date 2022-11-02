<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\ProductBundle\Provider\ProductImagesURLsProvider;
use Oro\Bundle\ProductBundle\Provider\WebpAwareProductImagesURLsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;

class WebpAwareProductImagesURLsProviderTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_ID = 1;

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private ProductImagesURLsProvider $productImagesURLsProvider;

    private ProductRepository|\PHPUnit\Framework\MockObject\MockObject $productRepository;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->productRepository);

        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->productImagesURLsProvider = new WebpAwareProductImagesURLsProvider(
            $managerRegistry,
            $this->attachmentManager,
            new ProductImageHelper()
        );
    }

    /**
     * @dataProvider getFilteredImagesByProductIdDataProvider
     */
    public function testGetFilteredImagesByProductId(
        Product $product,
        array $imageFiles,
        array $filtersNames,
        bool $isWebpEnabledIfSupported,
        array $expectedImages
    ): void {
        $this->productRepository
            ->expects(self::once())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($product);

        $this->attachmentManager
            ->expects(self::any())
            ->method('isWebpEnabledIfSupported')
            ->willReturn($isWebpEnabledIfSupported);

        $this->attachmentManager
            ->expects(self::any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(
                function (File $imageFile, string $filterName, string $format) use ($imageFiles, $filtersNames) {
                    self::assertContains($filterName, $filtersNames);
                    $format = $format ? '.' . $format : '';

                    return '/' . $filterName . array_search($imageFile, $imageFiles, true) . $format;
                }
            );

        $images = $this->productImagesURLsProvider->getFilteredImagesByProductId(self::PRODUCT_ID, $filtersNames);

        self::assertSame($expectedImages, $images);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getFilteredImagesByProductIdDataProvider(): array
    {
        $imageUrl1 = '/image-url-1.jpg';
        $imageUrl2 = '/image-url-2.jpg';
        $imageFile1 = $this->createMock(File::class);
        $imageFile2 = clone $imageFile1;
        $filterName1 = 'filter_name_1';
        $filterName2 = 'filter_name_2';

        $productImage1 = new StubProductImage();
        $productImage1->setImage($imageFile1)
            ->addType('listing');

        $productImage2 = (new StubProductImage())
            ->setImage($imageFile2);
        $productWithImages = (new ProductStub())
            ->setId(self::PRODUCT_ID);
        $productWithoutImages = clone $productWithImages;
        $productWithImages->addImage($productImage1)->addImage($productImage2);

        return [
            'webp is not enabled if supported' => [
                'product' => $productWithImages,
                'imageFiles' => [$imageUrl1 => $imageFile1, $imageUrl2 => $imageFile2],
                'filtersNames' => [$filterName1, $filterName2],
                'isWebpEnabledIfSupported' => false,
                'expectedImages' => [
                    [
                        'filter_name_1' => [
                            [
                                'srcset' => '/filter_name_1/image-url-1.jpg',
                                'type' => null,
                            ],
                        ],
                        'filter_name_2' => [
                            [
                                'srcset' => '/filter_name_2/image-url-1.jpg',
                                'type' => null,
                            ],
                        ],
                        'isInitial' => true,
                    ],
                    [
                        'filter_name_1' => [
                            [
                                'srcset' => '/filter_name_1/image-url-2.jpg',
                                'type' => null,
                            ],
                        ],
                        'filter_name_2' => [
                            [
                                'srcset' => '/filter_name_2/image-url-2.jpg',
                                'type' => null,
                            ],
                        ],
                        'isInitial' => false,
                    ],
                ],
            ],
            'webp is enabled if supported' => [
                'product' => $productWithImages,
                'imageFiles' => [$imageUrl1 => $imageFile1, $imageUrl2 => $imageFile2],
                'filtersNames' => [$filterName1, $filterName2],
                'isWebpEnabledIfSupported' => true,
                'expectedImages' => [
                    [
                        'filter_name_1' => [
                            [
                                'srcset' => '/filter_name_1/image-url-1.jpg.webp',
                                'type' => 'image/webp',
                            ],
                            [
                                'srcset' => '/filter_name_1/image-url-1.jpg',
                                'type' => null,
                            ],
                        ],
                        'filter_name_2' => [
                            [
                                'srcset' => '/filter_name_2/image-url-1.jpg.webp',
                                'type' => 'image/webp',
                            ],
                            [
                                'srcset' => '/filter_name_2/image-url-1.jpg',
                                'type' => null,
                            ],
                        ],
                        'isInitial' => true,
                    ],
                    [
                        'filter_name_1' => [
                            [
                                'srcset' => '/filter_name_1/image-url-2.jpg.webp',
                                'type' => 'image/webp',
                            ],
                            [
                                'srcset' => '/filter_name_1/image-url-2.jpg',
                                'type' => null,
                            ],
                        ],
                        'filter_name_2' => [
                            [
                                'srcset' => '/filter_name_2/image-url-2.jpg.webp',
                                'type' => 'image/webp',
                            ],
                            [
                                'srcset' => '/filter_name_2/image-url-2.jpg',
                                'type' => null,
                            ],
                        ],
                        'isInitial' => false,
                    ],
                ],
            ],
            'no image files' => [
                'product' => $productWithoutImages,
                'imageFiles' => [],
                'filtersNames' => [$filterName1, $filterName2],
                'isWebpEnabledIfSupported' => true,
                'expectedImages' => [],
            ],
        ];
    }

    public function testGetFilteredImagesByProductIdWhenNoFiltersNames(): void
    {
        $this->attachmentManager
            ->expects(self::never())
            ->method('isWebpEnabledIfSupported');

        $images = $this->productImagesURLsProvider->getFilteredImagesByProductId(self::PRODUCT_ID, []);
        self::assertSame([], $images);
    }
}
