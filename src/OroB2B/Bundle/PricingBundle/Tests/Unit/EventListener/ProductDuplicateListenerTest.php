<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\EventListener\ProductDuplicateListener;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListenerTest extends \PHPUnit_Framework_TestCase
{
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
     * @var ProductPriceRepository
     */
    protected $productPriceRepository;

    /**
     * @var ObjectManager
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

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productPriceRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
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
    }

    public function testOnDuplicateAfter()
    {
        $this->productPriceRepository->expects($this->once())
            ->method('getPricesByProduct')
            ->with($this->sourceProduct)
            ->will($this->returnValue(
                [new ProductPrice(), new ProductPrice(), new ProductPrice()]
            ));

        $this->objectManager
            ->expects($this->exactly(3))
            ->method('persist');

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->listener->onDuplicateAfter($event);
    }

    public function testOnDuplicateAfterSourceProductWithoutPrices()
    {
        $this->productPriceRepository->expects($this->once())
            ->method('getPricesByProduct')
            ->with($this->sourceProduct)
            ->will($this->returnValue(
                []
            ));

        $this->objectManager
            ->expects($this->never())
            ->method('persist');

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->listener->onDuplicateAfter($event);
    }
}
