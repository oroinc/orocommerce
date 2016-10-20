<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Prophecy\Prophecy\ObjectProphecy;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImageListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;

class ProductImageListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductImageListener
     */
    protected $listener;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function setUp()
    {
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->listener = new ProductImageListener($this->eventDispatcher->reveal());
    }

    public function testPostPersist()
    {
        $productImage = $this->prepareProductImage();
        $productImageNoTypes = $this->prepareProductImage($withTypes = false);

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImage, $forceOption = true)
        )->shouldBeCalledTimes(1);

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImageNoTypes, $forceOption = true)
        )->shouldNotBeCalled();

        $this->listener->postPersist($productImage, $this->prepareArgs()->reveal());
        $this->listener->postPersist($productImageNoTypes, $this->prepareArgs()->reveal());
    }

    public function testPostUpdate()
    {
        $productImage = $this->prepareProductImage();
        $productImageNoTypes = $this->prepareProductImage($withTypes = false);

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImage, $forceOption = true)
        )->shouldBeCalledTimes(1);

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImageNoTypes, $forceOption = true)
        )->shouldNotBeCalled();

        //update product image multiple times will dispatch event only once
        $this->listener->postUpdate($productImage, $this->prepareArgs()->reveal());
        $this->listener->postUpdate($productImage, $this->prepareArgs()->reveal());
        //update product image without types will not dispatch event
        $this->listener->postUpdate($productImageNoTypes, $this->prepareArgs()->reveal());
    }

    public function testFilePostUpdate()
    {
        $productImage = $this->prepareProductImage();

        $image = new File();
        $em = $this->prophesize(EntityManager::class);
        $repository = $this->prophesize(ProductRepository::class);

        $repository->findOneBy(['image' => $image])->willReturn($productImage);
        $em->getRepository(ProductImage::class)->willReturn($repository->reveal());

        $args = $this->prepareArgs();
        $args->getEntityManager()->willReturn($em->reveal());

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImage, $forceOption = true)
        )->shouldBeCalledTimes(1);

        //update image file multiple times will dispatch event only once
        $this->listener->filePostUpdate($image, $args->reveal());
        $this->listener->filePostUpdate($image, $args->reveal());
    }

    /**
     * @return ObjectProphecy
     */
    private function prepareArgs()
    {
        return $this->prophesize(LifecycleEventArgs::class);
    }

    /**
     * @param bool $withTypes
     * @return StubProductImage
     */
    private function prepareProductImage($withTypes = true)
    {
        $productImage = new StubProductImage();
        $productImage->setId(1);

        if ($withTypes) {
            $productImage->addType('type');
        }

        return $productImage;
    }
}
