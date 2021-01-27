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
    const CLASS_NAME = 'stdClass';

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var ProductDuplicateListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager */
    protected $objectManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectRepository */
    protected $repository;

    /** @var Product */
    protected $product;

    /** @var Product */
    protected $sourceProduct;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductDuplicateListener($this->doctrineHelper);
        $this->listener->setProductShippingOptionsClass(self::CLASS_NAME);

        $this->repository = $this->createMock('Doctrine\Persistence\ObjectRepository');
        $this->objectManager = $this->createMock('Doctrine\Persistence\ObjectManager');

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(self::CLASS_NAME)
            ->willReturn($this->repository);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(self::CLASS_NAME)
            ->willReturn($this->objectManager);

        $this->product = new Product();
        $this->sourceProduct = new Product();
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

        $this->objectManager->expects($this->exactly(3))->method('persist');
        $this->objectManager->expects($this->once())->method('flush');

        $this->listener->onDuplicateAfter(new ProductDuplicateAfterEvent($this->product, $this->sourceProduct));
    }

    public function testOnDuplicateAfterSourceProductWithoutOptions()
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['product' => $this->sourceProduct])
            ->willReturn([]);

        $this->objectManager->expects($this->never())->method('persist');
        $this->objectManager->expects($this->never())->method('flush');

        $this->listener->onDuplicateAfter(new ProductDuplicateAfterEvent($this->product, $this->sourceProduct));
    }
}
