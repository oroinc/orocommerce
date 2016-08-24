<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductImageRepository;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImageListener;

class ProductImageListenerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_IMAGE_CLASS = 'ProductImage';

    /**
     * @var ProductImageListener
     */
    protected $listener;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ProductImage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productImage1;

    /**
     * @var ProductImage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productImage2;

    public function setUp()
    {
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->listener = new ProductImageListener($this->eventDispatcher, self::PRODUCT_IMAGE_CLASS);

        $this->productImage1 = $this->getMock(ProductImage::class);
        $this->productImage1->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->productImage2 = $this->getMock(ProductImage::class);
        $this->productImage2->expects($this->once())
            ->method('getId')
            ->willReturn(2);
        $this->productImage2->expects($this->once())
            ->method('getTypes')
            ->willReturn(['type']);
    }

    public function testLifeCycles()
    {
        $this->checkPostPersist();
        $this->checkPostUpdate();
        $this->checkPostFlush();
    }

    protected function checkPostPersist()
    {
        $productImageType = $this->getMockBuilder(ProductImageType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productImageType->expects($this->once())
            ->method('getProductImage')
            ->willReturn($this->productImage1);

        $persistArgs = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $persistArgs->expects($this->once())
            ->method('getEntity')
            ->willReturn($productImageType);

        $this->listener->postPersist($persistArgs);
    }

    protected function checkPostUpdate()
    {
        $file = new File();

        $repository = $this->getMockBuilder(ProductImageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneByImage')
            ->with($file)
            ->willReturn($this->productImage2);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with(self::PRODUCT_IMAGE_CLASS)
            ->willReturn($repository);

        $updateArgs = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateArgs->expects($this->once())
            ->method('getEntity')
            ->willReturn($file);
        $updateArgs->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->listener->postUpdate($updateArgs);
    }

    protected function checkPostFlush()
    {
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(
                ProductImageResizeEvent::NAME,
                $this->callback(
                    function (ProductImageResizeEvent $event) {
                        return $event->getProductImage() === $this->productImage1 &&
                        $event->getForceOption() === false;
                    }
                )
            );
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(
                ProductImageResizeEvent::NAME,
                $this->callback(
                    function (ProductImageResizeEvent $event) {
                        return $event->getProductImage() === $this->productImage2 &&
                        $event->getForceOption() === true;
                    }
                )
            );

        $args = $this->getMockBuilder(PostFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener->postFlush($args);
    }
}
