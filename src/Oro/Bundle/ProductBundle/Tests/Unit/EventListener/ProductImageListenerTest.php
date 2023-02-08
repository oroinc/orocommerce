<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImageListener;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductImageListenerTest extends \PHPUnit\Framework\TestCase
{
    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject $imageTypeProvider;

    private ProductImageHelper|\PHPUnit\Framework\MockObject\MockObject $productImageHelper;

    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $productImageEntityManager;

    private LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject $lifecycleArgs;

    private ProductRepository|\PHPUnit\Framework\MockObject\MockObject $productRepository;

    private ProductImageListener $listener;

    protected function setUp(): void
    {
        $this->productImageEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->productImageHelper = $this->createMock(ProductImageHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->lifecycleArgs = $this->createMock(LifecycleEventArgs::class);
        $this->lifecycleArgs->expects(self::any())
            ->method('getObjectManager')
            ->willReturn($this->productImageEntityManager);

        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->listener = new ProductImageListener(
            $this->eventDispatcher,
            $this->imageTypeProvider,
            $this->productImageHelper
        );
    }

    public function testPostPersist(): void
    {
        $this->imageTypeProvider->expects(self::any())
            ->method('getMaxNumberByType')
            ->willReturn(
                [
                    'main' => [
                        'max' => 1,
                        'label' => 'Main'
                    ],
                    'listing' => [
                        'max' => 1,
                        'label' => 'Listing'
                    ]
                ]
            );

        $this->productImageHelper->expects(self::once())
            ->method('countImagesByType')
            ->willReturn(
                [
                    'main' => 1,
                    'listing' => 1,
                ]
            );

        $productImage = $this->prepareProductImage(35, 101);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ProductImageResizeEvent($productImage->getId(), true),
                ProductImageResizeEvent::NAME
            );

        $this->listener->postPersist($productImage, $this->lifecycleArgs);
    }

    public function testPostPersistWhenStoredExternally(): void
    {
        $this->imageTypeProvider->expects(self::any())
            ->method('getMaxNumberByType')
            ->willReturn(
                [
                    'main' => [
                        'max' => 1,
                        'label' => 'Main'
                    ],
                    'listing' => [
                        'max' => 1,
                        'label' => 'Listing'
                    ]
                ]
            );

        $this->productImageHelper->expects(self::once())
            ->method('countImagesByType')
            ->willReturn(
                [
                    'main' => 1,
                    'listing' => 1,
                ]
            );

        $productImage = $this->prepareProductImage(35, 101);
        $productImage->getImage()->setExternalUrl('http://example.org/image.png');

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->postPersist($productImage, $this->lifecycleArgs);
    }

    public function testPostFlushDispatchReindexationRequestEventWhenStoredExternally(): void
    {
        $this->imageTypeProvider->expects(self::any())
            ->method('getMaxNumberByType')
            ->willReturn(
                [
                    'main' => [
                        'max' => 1,
                        'label' => 'Main'
                    ],
                    'listing' => [
                        'max' => 1,
                        'label' => 'Listing'
                    ]
                ]
            );

        $this->productImageHelper->expects(self::once())
            ->method('countImagesByType')
            ->willReturn(
                [
                    'main' => 1,
                    'listing' => 1,
                ]
            );

        $productImage = $this->prepareProductImage(35, 101);
        $productImage->getImage()->setExternalUrl('http://example.org/image.png');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent(
                    [Product::class],
                    [],
                    [101 => 101],
                    true,
                    ['image']
                ),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->listener->postPersist($productImage, $this->lifecycleArgs);

        $this->listener->postFlush(new PostFlushEventArgs($this->productImageEntityManager));
    }

    public function testPostPersistForNotMainAndListingImage(): void
    {
        $this->imageTypeProvider->expects(self::any())
            ->method('getMaxNumberByType')
            ->willReturn(
                [
                    'main' => [
                        'max' => 1,
                        'label' => 'Main'
                    ],
                    'listing' => [
                        'max' => 1,
                        'label' => 'Listing'
                    ],
                    'additional' => [
                        'max' => null,
                        'label' => 'Additional'
                    ],
                ]
            );

        $this->productImageHelper->expects(self::once())
            ->method('countImagesByType')
            ->willReturn(
                [
                    'main' => 3,
                    'listing' => 3,
                    'additional' => 3,
                ]
            );

        $mainImage1 = new StubProductImage();
        $mainImage1->addType(new ProductImageType('main'));

        $mainImage2 = new StubProductImage();
        $mainImage2->addType(new ProductImageType('main'));

        $listingImage1 = new StubProductImage();
        $listingImage1->addType(new ProductImageType('listing'));

        $listingImage2 = new StubProductImage();
        $listingImage2->addType(new ProductImageType('listing'));

        $additionalImage1 = new StubProductImage();
        $additionalImage1->addType(new ProductImageType('additional'));

        $additionalImage2 = new StubProductImage();
        $additionalImage2->addType(new ProductImageType('additional'));

        $newImage = new StubProductImage();
        $newImage->addType(new ProductImageType('main'));
        $newImage->addType(new ProductImageType('listing'));
        $newImage->addType(new ProductImageType('additional'));

        $product = new Product();
        $product->addImage($mainImage1);
        $product->addImage($mainImage2);
        $product->addImage($listingImage1);
        $product->addImage($listingImage2);
        $product->addImage($additionalImage1);
        $product->addImage($additionalImage2);
        $product->addImage($newImage);

        $this->listener->postPersist($newImage, $this->lifecycleArgs);

        $this->assertProductImageTypes([], $mainImage1);
        $this->assertProductImageTypes([], $mainImage2);

        $this->assertProductImageTypes([], $listingImage1);
        $this->assertProductImageTypes([], $listingImage2);

        $this->assertProductImageTypes(['additional'], $additionalImage1);
        $this->assertProductImageTypes(['additional'], $additionalImage2);

        $this->assertProductImageTypes(['main', 'listing', 'additional'], $newImage);
    }

    private function assertProductImageTypes(array $expected, StubProductImage $productImage): void
    {
        $types = array_map(
            static function (ProductImageType $productImageType) {
                return $productImageType->getType();
            },
            $productImage->getTypes()->toArray()
        );

        self::assertEquals($expected, $types);
    }

    public function testPostUpdate(): void
    {
        $productImage = $this->prepareProductImage(24, 102);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ProductImageResizeEvent($productImage->getId(), true),
                ProductImageResizeEvent::NAME
            )
            ->willReturnArgument(0);

        $this->listener->postUpdate($productImage, $this->lifecycleArgs);
    }

    public function testPostUpdateWhenStoredExternally(): void
    {
        $productImage = $this->prepareProductImage(24, 102);
        $productImage->getImage()->setExternalUrl('http://example.org/image.png');

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->postUpdate($productImage, $this->lifecycleArgs);
    }

    public function testFilePostUpdate(): void
    {
        $productImage = $this->prepareProductImage(76, 103);

        $this->productRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($productImage);

        $this->productImageEntityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->productRepository);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ProductImageResizeEvent($productImage->getId(), true),
                ProductImageResizeEvent::NAME
            )
            ->willReturnArgument(0);

        $this->listener->filePostUpdate(new File(), $this->lifecycleArgs);
    }

    public function testFilePostUpdateWhenStoredExternally(): void
    {
        $productImage = $this->prepareProductImage(76, 103);
        $productImage->getImage()->setExternalUrl('http://example.org/image.png');

        $this->productRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($productImage);

        $this->productImageEntityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->productRepository);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->filePostUpdate($productImage->getImage(), $this->lifecycleArgs);
    }

    public function testPostFlush(): void
    {
        $this->listener->postUpdate($this->prepareProductImage(10, 101), $this->lifecycleArgs);
        $this->listener->postUpdate($this->prepareProductImage(11, 101), $this->lifecycleArgs);
        $this->listener->postUpdate($this->prepareProductImage(12, 102), $this->lifecycleArgs);
        $this->listener->postUpdate($this->prepareProductImage(13, 103), $this->lifecycleArgs);
        $this->listener->postUpdate($this->prepareProductImage(14, 103), $this->lifecycleArgs);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent(
                    [Product::class],
                    [],
                    [
                        101 => 101,
                        102 => 102,
                        103 => 103,
                    ],
                    true,
                    ['image']
                ),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->listener->postFlush(new PostFlushEventArgs($this->productImageEntityManager));
    }

    private function prepareProductImage(int $imageId, int $productId): StubProductImage
    {
        $parentProductImage = new StubProductImage();
        $parentProductImage->setImage(new File());
        $parentProductImage->setTypes(
            new ArrayCollection(
                [
                    new ProductImageType('main'),
                    new ProductImageType('listing')
                ]
            )
        );

        $parentProduct = new ProductStub();
        $parentProduct->setId($productId);
        $parentProduct->addImage($parentProductImage);

        $productImage = new StubProductImage();
        $productImage->setId($imageId);
        $productImage->setImage(new File());
        $productImage->addType(new ProductImageType('main'));
        $productImage->setProduct($parentProduct);

        return $productImage;
    }
}
