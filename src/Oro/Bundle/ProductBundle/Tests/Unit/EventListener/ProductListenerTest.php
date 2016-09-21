<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;

use Prophecy\Argument;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;

class ProductListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductListener
     */
    protected $listener;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function setUp()
    {
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->listener = new ProductListener($this->eventDispatcher->reveal());
    }

    public function testPostUpdate()
    {
        $file2 = new File();
        $file3 = new File();

        $productImage1 = new StubProductImage();
        $productImage2 = new StubProductImage();
        $productImage2->addType('type');
        $productImage2->setImage($file2);
        $productImage3 = new StubProductImage();
        $productImage3->addType('othertype');
        $productImage3->setImage($file3);

        $product = new Product();
        $product->addImage($productImage1);
        $product->addImage($productImage2);
        $product->addImage($productImage3);

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            Argument::type(ProductImageResizeEvent::class)
        )->shouldBeCalledTimes(2);

        $this->listener->postUpdate($product, $this->prepareArgs([
            [$productImage2, ['somechanges']],
            [$file2, []],
            [$productImage3, []],
            [$file3, ['notempty']]
        ]));
    }

    /**
     * @param array $changeSets
     * @return LifecycleEventArgs
     */
    private function prepareArgs(array $changeSets)
    {
        $unitOfWork = $this->prophesize(UnitOfWork::class);
        foreach ($changeSets as $changeSet) {
            list($entity, $set) = $changeSet;
            $unitOfWork->getEntityChangeSet($entity)->willReturn($set);
        }

        $em = $this->prophesize(EntityManager::class);
        $em->getUnitOfWork()->willReturn($unitOfWork->reveal());

        $args = $this->prophesize(LifecycleEventArgs::class);
        $args->getEntityManager()->willReturn($em->reveal());

        return $args->reveal();
    }
}
