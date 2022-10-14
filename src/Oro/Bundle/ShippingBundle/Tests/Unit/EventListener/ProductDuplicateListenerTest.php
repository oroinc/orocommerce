<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\EventListener\ProductDuplicateListener;

class ProductDuplicateListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = 'stdClass';

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager */
    private $objectManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectRepository */
    private $repository;

    /** @var Product */
    private $product;

    /** @var Product */
    private $sourceProduct;

    /** @var ProductDuplicateListener */
    private $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ObjectRepository::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->product = new Product();
        $this->sourceProduct = new Product();

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(self::CLASS_NAME)
            ->willReturn($this->repository);
        $doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(self::CLASS_NAME)
            ->willReturn($this->objectManager);

        $this->listener = new ProductDuplicateListener($doctrineHelper);
        $this->listener->setProductShippingOptionsClass(self::CLASS_NAME);
    }

    public function testOnDuplicateAfter()
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['product' => $this->sourceProduct])
            ->willReturn(
                [
                    new ProductShippingOptions(),
                    new ProductShippingOptions(),
                    new ProductShippingOptions()
                ]
            );

        $this->objectManager->expects($this->exactly(3))
            ->method('persist');
        $this->objectManager->expects($this->once())
            ->method('flush');

        $this->listener->onDuplicateAfter(new ProductDuplicateAfterEvent($this->product, $this->sourceProduct));
    }

    public function testOnDuplicateAfterSourceProductWithoutOptions()
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['product' => $this->sourceProduct])
            ->willReturn([]);

        $this->objectManager->expects($this->never())
            ->method('persist');
        $this->objectManager->expects($this->never())
            ->method('flush');

        $this->listener->onDuplicateAfter(new ProductDuplicateAfterEvent($this->product, $this->sourceProduct));
    }
}
