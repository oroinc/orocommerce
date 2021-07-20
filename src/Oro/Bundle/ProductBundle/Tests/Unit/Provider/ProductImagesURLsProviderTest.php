<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\ProductBundle\Provider\ProductImagesURLsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductImagesURLsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @internal
     */
    const PRODUCT_ID = 1;

    /**
     * @var ProductImagesURLsProvider
     */
    private $productImagesURLsProvider;

    /**
     * @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attachmentManager;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->productImagesURLsProvider =
            new ProductImagesURLsProvider($this->doctrineHelper, $this->attachmentManager, new ProductImageHelper());
    }

    /**
     * @dataProvider getFilteredImagesByProductIdDataProvider
     */
    public function testGetFilteredImagesByProductId(
        Product $product,
        array $imageFiles,
        array $filtersNames,
        array $expectedImages
    ) {
        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($productRepository = $this->createMock(ProductRepository::class));

        $productRepository
            ->expects(static::once())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($product);

        $this->attachmentManager
            ->expects(static::any())
            ->method('getFilteredImageUrl')
            ->with($this->isInstanceOf(File::class), $this->callback(function ($filterName) use ($filtersNames) {
                return in_array($filterName, $filtersNames, true);
            }))
            ->willReturnCallback(function ($imageFile, $filterName) use ($imageFiles) {
                return '/' . $filterName . array_search($imageFile, $imageFiles, true);
            });

        $images = $this->productImagesURLsProvider->getFilteredImagesByProductId(self::PRODUCT_ID, $filtersNames);

        static::assertSame($expectedImages, $images);
    }

    /**
     * @return array
     */
    public function getFilteredImagesByProductIdDataProvider()
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
        $productWithImages = $this->getEntity(Product::class, [
            'id' => self::PRODUCT_ID,
        ]);
        $productWithoutImages = clone $productWithImages;
        $productWithImages->addImage($productImage1)->addImage($productImage2);

        return [
            'normal behavior' => [
                'product' => $productWithImages,
                'imageFiles' => [$imageUrl1 => $imageFile1, $imageUrl2 => $imageFile2],
                'filtersNames' => [$filterName1, $filterName2],
                'expectedImages' => [
                    [
                        'filter_name_1' => '/filter_name_1/image-url-1.jpg',
                        'filter_name_2' => '/filter_name_2/image-url-1.jpg',
                        'isInitial'     => true
                    ],
                    [
                        'filter_name_1' => '/filter_name_1/image-url-2.jpg',
                        'filter_name_2' => '/filter_name_2/image-url-2.jpg',
                        'isInitial'     => false
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

    public function testGetFilteredImagesByProductIdWhenNoFiltersNames()
    {
        $images = $this->productImagesURLsProvider->getFilteredImagesByProductId(self::PRODUCT_ID, []);
        static::assertSame([], $images);
    }
}
