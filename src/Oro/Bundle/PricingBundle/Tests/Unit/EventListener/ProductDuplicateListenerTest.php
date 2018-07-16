<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\EventListener\ProductDuplicateListener;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShardManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shardManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
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
     * @var ProductPriceRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productPriceRepository;

    /**
     * @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManager;

    /**
     * @var PriceManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceManager;

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

        $this->priceManager = $this->createMock(PriceManager::class);

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
        $this->listener->setShardManager($this->shardManager);
        $this->listener->setPriceManager($this->priceManager);
    }

    public function testOnDuplicateAfter()
    {
        $price1 = new ProductPrice();
        $price1->setId('price1Id');
        $priceRule1 = new PriceRule();
        $price1->setPriceRule($priceRule1);
        $this->productPriceRepository->expects($this->once())
            ->method('getPricesByProduct')
            ->with($this->shardManager, $this->sourceProduct)
            ->will($this->returnValue(
                [$price1, new ProductPrice(), new ProductPrice()]
            ));

        $this->priceManager->expects($this->exactly(3))
            ->method('persist')
        ->withConsecutive(
            [(new ProductPrice())->setProduct($this->product)->setPriceRule($priceRule1)],
            [(new ProductPrice())->setProduct($this->product)],
            [(new ProductPrice())->setProduct($this->product)]
        );
        $this->priceManager->expects($this->once())
            ->method('flush');

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

        $this->priceManager->expects($this->never())
            ->method('persist');
        $this->priceManager->expects($this->once())
            ->method('flush');

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->listener->onDuplicateAfter($event);
    }
}
