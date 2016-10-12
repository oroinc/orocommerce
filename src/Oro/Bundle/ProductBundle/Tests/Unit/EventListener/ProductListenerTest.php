<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

use Prophecy\Argument;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImageListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;

class ProductListenerTest extends \PHPUnit_Framework_TestCase
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

    public function testDispatchResizeEventOnTypesChange()
    {
        $productImage = new StubProductImage();
        $productImage->addType('type');
        $productImage->setId(1);

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            Argument::type(ProductImageResizeEvent::class)
        )->shouldBeCalledTimes(1);

        $this->listener->postPersist($productImage, $this->prepareArgs());
        $this->listener->postUpdate($productImage, $this->prepareArgs());
    }

    public function testDispatchResizeEventOnFileChange()
    {
        $productImage = $this->prophesize(ProductImage::class);
        $productImage->getId()->willReturn(1);
        $productImage->hasUploadedFile()->willReturn(true);

        $productImage->setUpdatedAtToNow()->shouldBeCalled();

        $this->listener->preFlush($productImage->reveal(), $this->prophesize(PreFlushEventArgs::class)->reveal());
    }

    public function testDoesNothingIfProductImageDoesNotHaveTypes()
    {
        $productImage = new StubProductImage();

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            Argument::type(ProductImageResizeEvent::class)
        )->shouldNotBeCalled();

        $this->listener->postPersist($productImage, $this->prepareArgs());
        $this->listener->postUpdate($productImage, $this->prepareArgs());
    }

    /**
     * @return LifecycleEventArgs
     */
    private function prepareArgs()
    {
        return $this->prophesize(LifecycleEventArgs::class)->reveal();
    }
}
