<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
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
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductImageListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $imageTypeProvider;

    /** @var ProductImageHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $productImageHelper;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productImageEntityManager;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject */
    private $lifecycleArgs;

    /** @var ProductImageListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->productImageEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->productImageHelper = $this->createMock(ProductImageHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->lifecycleArgs = $this->createMock(LifecycleEventArgs::class);
        $this->lifecycleArgs->expects(self::any())
            ->method('getObjectManager')
            ->willReturn($this->productImageEntityManager);

        $container = TestContainerBuilder::create()
            ->add(ImageTypeProvider::class, $this->imageTypeProvider)
            ->add(ProductImageHelper::class, $this->productImageHelper)
            ->getContainer($this);

        $this->listener = new ProductImageListener($this->eventDispatcher, $container);
    }

    private function prepareProductImage(int $imageId, int $productId): StubProductImage
    {
        $parentProductImage = new StubProductImage();
        $parentProductImage->setImage(new File());
        $parentProductImage->setTypes(
            new ArrayCollection([new ProductImageType('main'), new ProductImageType('listing')])
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

    /**
     * @dataProvider changedProductEntityDataProvider
     */
    public function testOnFlushSchedulesReindexForChangedEntity(
        array $insertions,
        array $updates,
        array $deletions,
        int $productId
    ): void {
        $entityManager = $this->getFlushEntityManager($insertions, $updates, $deletions);
        $this->expectReindexDispatch([$productId => $productId]);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
        $this->listener->postFlush(new PostFlushEventArgs($entityManager));
    }

    public static function changedProductEntityDataProvider(): array
    {
        $insertedType = new ProductImageType(ProductImageType::TYPE_MAIN);
        $insertedType->setProductImage(self::productImageForProduct(201));

        $updatedType = new ProductImageType(ProductImageType::TYPE_MAIN);
        $updatedType->setProductImage(self::productImageForProduct(202));

        $deletedType = new ProductImageType(ProductImageType::TYPE_MAIN);
        $deletedType->setProductImage(self::productImageForProduct(203));

        $dedupProductImage = self::productImageForProduct(206);
        $dedupMainType = new ProductImageType(ProductImageType::TYPE_MAIN);
        $dedupMainType->setProductImage($dedupProductImage);
        $dedupListingType = new ProductImageType(ProductImageType::TYPE_LISTING);
        $dedupListingType->setProductImage($dedupProductImage);

        return [
            'inserted ProductImageType' => [[$insertedType], [], [], 201],
            'updated ProductImageType' => [[], [$updatedType], [], 202],
            'deleted ProductImageType' => [[], [], [$deletedType], 203],
            'deleted ProductImage' => [[], [], [self::productImageForProduct(204)], 204],
            // Insertion without types.
            'inserted ProductImage without types' => [[self::productImageForProduct(205)], [], [], 205],
            // Two types of the same product image in one flush must still yield a single id.
            'deduplicates several changes to the same product' => [[$dedupMainType, $dedupListingType], [], [], 206],
        ];
    }

    public function testOnFlushIgnoresUnrelatedEntities(): void
    {
        $entityManager = $this->getFlushEntityManager(
            insertions: [new \stdClass(), new Product()],
            updates: [new File()],
            deletions: [new \stdClass()]
        );

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
        $this->listener->postFlush(new PostFlushEventArgs($entityManager));
    }

    /**
     * @dataProvider entitiesWithoutResolvableProductIdDataProvider
     */
    public function testOnFlushIgnoresEntityWithoutResolvableProductId(object $entity): void
    {
        $entityManager = $this->getFlushEntityManager(insertions: [$entity]);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
        $this->listener->postFlush(new PostFlushEventArgs($entityManager));
    }

    public function entitiesWithoutResolvableProductIdDataProvider(): array
    {
        $productImageWithoutProduct = new StubProductImage();

        $productImageWithUnsavedProduct = new StubProductImage();
        $productImageWithUnsavedProduct->setProduct(new ProductStub());

        return [
            'ProductImageType without parent ProductImage' => [new ProductImageType(ProductImageType::TYPE_MAIN)],
            'ProductImage without Product' => [$productImageWithoutProduct],
            'ProductImage whose Product has no id yet' => [$productImageWithUnsavedProduct],
        ];
    }

    public function testOnClearResetsBufferForProductImageType(): void
    {
        $type = new ProductImageType(ProductImageType::TYPE_MAIN);
        $type->setProductImage($this->productImageForProduct(207));

        $entityManager = $this->getFlushEntityManager(insertions: [$type]);
        $this->listener->onFlush(new OnFlushEventArgs($entityManager));

        $onClearArgs = $this->createMock(OnClearEventArgs::class);
        $onClearArgs->expects(self::once())
            ->method('getEntityClass')
            ->willReturn(ProductImageType::class);
        $this->listener->onClear($onClearArgs);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->postFlush(new PostFlushEventArgs($entityManager));
    }

    private static function productImageForProduct(int $productId): StubProductImage
    {
        $product = new ProductStub();
        $product->setId($productId);

        $productImage = new StubProductImage();
        $productImage->setProduct($product);

        return $productImage;
    }

    private function expectReindexDispatch(array $productIds): void
    {
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent([Product::class], [], $productIds, true, ['image']),
                ReindexationRequestEvent::EVENT_NAME
            );
    }

    private function getFlushEntityManager(
        array $insertions = [],
        array $updates = [],
        array $deletions = []
    ): EntityManagerInterface {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertions);
        $unitOfWork->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $unitOfWork->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $entityManager;
    }
}
