<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\EventListener\ProductUnitPrecisionPostRemoveListener;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductUnitPrecisionPostRemoveListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ShardManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shardManager;

    /**
     * @var ProductUnitPrecisionPostRemoveListener
     * */
    private $listener;

    /** {@inheritdoc} */
    protected function setUp(): void
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->listener = new ProductUnitPrecisionPostRemoveListener($this->shardManager);
        $this->listener->setPriceAttributeClass(PriceAttributeProductPrice::class);
    }

    public function testPostRemoveForOtherEntity()
    {
        $entity = new \stdClass();
        $event = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $event->expects($this->never())
            ->method('getEntityManager');

        $this->listener->postRemove($event);
    }

    public function testPostRemoveForNewProduct()
    {
        $product = new Product();
        $unit = new ProductUnit();
        $entity = new ProductUnitPrecision();

        $entity->setProduct($product)
            ->setUnit($unit);

        $event = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $event->expects($this->never())
            ->method('getEntityManager');

        $this->listener->postRemove($event);
    }

    public function testPostRemove()
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $unit = new ProductUnit();
        $entity = new ProductUnitPrecision();

        $entity->setProduct($product)
            ->setUnit($unit);

        $event = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $repository = $this->getMockBuilder(PriceAttributeProductPriceRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('deleteByProductUnit')
            ->with($this->shardManager, $product, $unit);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->with(PriceAttributeProductPrice::class)
            ->will($this->returnValue($repository));

        $event->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->listener->postRemove($event);
    }
}
