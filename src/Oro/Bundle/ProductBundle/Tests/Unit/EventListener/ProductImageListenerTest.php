<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\EventListener\ProductImageListener;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
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

    public function setUp()
    {
        $this->productImageEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->productImageHelper = $this->createMock(ProductImageHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->lifecycleArgs = $this->createMock(LifecycleEventArgs::class);
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

        $productImage = $this->prepareProductImage();

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn(true);

        $this->lifecycleArgs->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($this->productImageEntityManager);

        $this->listener->postPersist($productImage, $this->lifecycleArgs);
    }

    public function testPostUpdate()
    {
        $productImage = $this->prepareProductImage();

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn(true);

        $this->listener->postUpdate($productImage, $this->lifecycleArgs);
    }

    public function testFilePostUpdate()
    {
        $this->lifecycleArgs->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($this->productImageEntityManager);

        $productImage = $this->prepareProductImage();

        $this->productRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($productImage);

        $this->productImageEntityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->productRepository);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn(true);

        $this->listener->filePostUpdate(new File(), $this->lifecycleArgs);
    }

    /**
     * @return StubProductImage
     */
    private function prepareProductImage()
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

        $parentProduct = new Product();
        $parentProduct->addImage($parentProductImage);

        $productImage = new StubProductImage();
        $productImage->setImage(new File());
        $productImage->addType(new ProductImageType('main'));
        $productImage->setProduct($parentProduct);

        return $productImage;
    }
}
