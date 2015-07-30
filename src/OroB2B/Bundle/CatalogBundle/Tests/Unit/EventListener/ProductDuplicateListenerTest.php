<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use OroB2B\Bundle\CatalogBundle\EventListener\ProductDuplicateListener;

class ProductDuplicateListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $categoryClass = 'stdClass';

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var ProductDuplicateListener
     */
    protected $listener;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

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
        $this->category = new Category();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryRepository = $this
            ->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryRepository->expects($this->once())
            ->method('findOneByProduct')
            ->will($this->returnCallback(function () {
                $args = func_get_args();
                $product = $args[0];

                if ($this->category->getProducts()->contains($product)) {
                    return $this->category;
                } else {
                    return null;
                }
            }));

        $this->objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->categoryClass)
            ->will($this->returnValue($this->categoryRepository));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($this->categoryClass)
            ->will($this->returnValue($this->objectManager));

        $this->listener = new ProductDuplicateListener();
        $this->listener->setCategoryClass($this->categoryClass);
        $this->listener->setDoctrineHelper($this->doctrineHelper);
    }

    public function testOnDuplicateAfterSourceProductLinkedWithCategory()
    {
        $this->category->getProducts()->clear();
        $this->category->addProduct($this->sourceProduct);

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->listener->onDuplicateAfter($event);

        $this->assertCount(2, $this->category->getProducts());
        $this->assertTrue($this->category->getProducts()->contains($this->product));
    }

    public function testOnDuplicateAfterSourceProductNotLinkedWithCategory()
    {
        $this->category->getProducts()->clear();

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->listener->onDuplicateAfter($event);

        $this->assertCount(0, $this->category->getProducts());
    }
}
