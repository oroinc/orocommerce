<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
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
    /**
     * @var ProductImageListener $listener
     */
    protected $listener;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject $imageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var ProductImageHelper|\PHPUnit\Framework\MockObject\MockObject $productImageHelper
     */
    protected $productImageHelper;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $productImageEntityManager
     */
    protected $productImageEntityManager;

    /**
     * @var LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject $lifecycleArgs
     */
    protected $lifecycleArgs;

    /**
     * @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject $productRepository
     */
    protected $productRepository;

    protected function setUp(): void
    {
        $this->productImageEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->productImageHelper = $this->createMock(ProductImageHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->lifecycleArgs = $this->createMock(LifecycleEventArgs::class);
        $this->lifecycleArgs->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->productImageEntityManager);

        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->listener = new ProductImageListener(
            $this->eventDispatcher,
            $this->imageTypeProvider,
            $this->productImageHelper
        );
    }

    public function testPostPersist()
    {
        $this->imageTypeProvider->expects($this->any())
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

        $this->productImageHelper->expects($this->once())
            ->method('countImagesByType')
            ->willReturn(
                [
                    'main' => 1,
                    'listing' => 1,
                ]
            );

        $productImage = $this->prepareProductImage(35, 101);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new ProductImageResizeEvent($productImage->getId(), true),
                ProductImageResizeEvent::NAME
            );

        $this->listener->postPersist($productImage, $this->lifecycleArgs);
    }

    public function testPostPersistForNotMAinAndListingImage()
    {
        $this->imageTypeProvider->expects($this->any())
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

        $this->productImageHelper->expects($this->once())
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

        $this->assertEquals($expected, $types);
    }

    public function testPostUpdate()
    {
        $productImage = $this->prepareProductImage(24, 102);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new ProductImageResizeEvent($productImage->getId(), true),
                ProductImageResizeEvent::NAME
            )
            ->willReturn(true);

        $this->listener->postUpdate($productImage, $this->lifecycleArgs);
    }

    public function testFilePostUpdate()
    {
        $productImage = $this->prepareProductImage(76, 103);

        $this->productRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($productImage);

        $this->productImageEntityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->productRepository);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new ProductImageResizeEvent($productImage->getId(), true),
                ProductImageResizeEvent::NAME
            )
            ->willReturn(true);

        $this->listener->filePostUpdate(new File(), $this->lifecycleArgs);
    }

    public function testPostFlush()
    {
        $this->listener->postUpdate($this->prepareProductImage(10, 101), $this->lifecycleArgs);
        $this->listener->postUpdate($this->prepareProductImage(11, 101), $this->lifecycleArgs);
        $this->listener->postUpdate($this->prepareProductImage(12, 102), $this->lifecycleArgs);
        $this->listener->postUpdate($this->prepareProductImage(13, 103), $this->lifecycleArgs);
        $this->listener->postUpdate($this->prepareProductImage(14, 103), $this->lifecycleArgs);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent(
                    [Product::class],
                    [],
                    [
                        101 => 101,
                        102 => 102,
                        103 => 103,
                    ]
                ),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->listener->postFlush(new PostFlushEventArgs($this->productImageEntityManager));
    }

    /**
     * @param int $imageId
     * @param int $productId
     * @return StubProductImage
     */
    private function prepareProductImage(int $imageId, int $productId)
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
