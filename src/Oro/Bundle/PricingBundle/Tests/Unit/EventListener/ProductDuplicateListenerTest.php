<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\EventListener\ProductDuplicateListener;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShardManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shardManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $productPriceClass = 'stdClass';

    /**
     * @var ProductDuplicateListener
     */
    protected $listener;

    /**
     * @var ProductPriceRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productPriceRepository;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var Product
     */
    protected $sourceProduct;

    protected function setUp()
    {
        $this->product = new Product();
        $this->sourceProduct = new Product();

        $this->shardManager = $this->createMock(ShardManager::class);

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productPriceRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with($this->productPriceClass)
            ->will($this->returnValue($this->productPriceRepository));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($this->productPriceClass)
            ->will($this->returnValue($this->objectManager));

        $this->listener = new ProductDuplicateListener();
        $this->listener->setProductPriceClass($this->productPriceClass);
        $this->listener->setDoctrineHelper($this->doctrineHelper);
        $this->listener->setshardManager($this->shardManager);
    }

    public function testOnDuplicateAfter()
    {
        $this->productPriceRepository->expects($this->once())
            ->method('getPricesByProduct')
            ->with($this->shardManager, $this->sourceProduct)
            ->will($this->returnValue(
                [new ProductPrice(), new ProductPrice(), new ProductPrice()]
            ));

        $this->productPriceRepository
            ->expects($this->exactly(3))
            ->method('save');

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->listener->onDuplicateAfter($event);
    }

    public function testOnDuplicateAfterSourceProductWithoutPrices()
    {
        $this->productPriceRepository->expects($this->once())
            ->method('getPricesByProduct')
            ->with($this->shardManager, $this->sourceProduct)
            ->will($this->returnValue(
                []
            ));

        $this->productPriceRepository
            ->expects($this->never())
            ->method('save');

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->listener->onDuplicateAfter($event);
    }
}
