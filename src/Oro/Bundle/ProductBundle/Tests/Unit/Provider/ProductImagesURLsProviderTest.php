<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\ProductBundle\Provider\ProductImagesURLsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;

class ProductImagesURLsProviderTest extends \PHPUnit\Framework\TestCase
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
        $this->productImagesURLsProvider =
            new ProductImagesURLsProvider($managerRegistry, $this->attachmentManager, new ProductImageHelper());
    }

    /**
     * @dataProvider getFilteredImagesByProductIdDataProvider
     */
    public function testGetFilteredImagesByProductId(
        Product $product,
        array $imageFiles,
        array $filtersNames,
        array $expectedImages
    ): void {
        $this->productRepository
            ->expects(self::once())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($product);

        $this->attachmentManager
            ->expects(self::any())
            ->method('getFilteredImageUrl')
            ->with(
                self::isInstanceOf(File::class),
                self::callback(function ($filterName) use ($filtersNames) {
                    return in_array($filterName, $filtersNames, true);
                })
            )
            ->willReturnCallback(function ($imageFile, $filterName) use ($imageFiles) {
                return '/' . $filterName . array_search($imageFile, $imageFiles, true);
            });

        $images = $this->productImagesURLsProvider->getFilteredImagesByProductId(self::PRODUCT_ID, $filtersNames);

        self::assertSame($expectedImages, $images);
    }

    /**
     * @return array
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
            'normal behavior' => [
                'product' => $productWithImages,
                'imageFiles' => [$imageUrl1 => $imageFile1, $imageUrl2 => $imageFile2],
                'filtersNames' => [$filterName1, $filterName2],
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

            'no image files' => [
                'product' => $productWithoutImages,
                'imageFiles' => [],
                'filtersNames' => [$filterName1, $filterName2],
                'expectedImages' => [],
            ],
        ];
    }

    public function testGetFilteredImagesByProductIdWhenNoFiltersNames(): void
    {
        $images = $this->productImagesURLsProvider->getFilteredImagesByProductId(self::PRODUCT_ID, []);
        self::assertSame([], $images);
    }
}
