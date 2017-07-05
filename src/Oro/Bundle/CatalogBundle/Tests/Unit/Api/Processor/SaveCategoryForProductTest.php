<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CatalogBundle\Api\Processor\SaveCategoryForProduct;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;

class SaveCategoryForProductTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var SaveCategoryForProduct
     */
    protected $saveCategoryForProduct;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repo;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->repo = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($this->repo);
        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->saveCategoryForProduct = new SaveCategoryForProduct($this->doctrineHelper);
    }

    public function testProcessShouldBeIgnored()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->never())
            ->method('setResult');

        $this->saveCategoryForProduct->process($context);
    }

    public function testProcessShouldRemoveOldCategoryAndAddNewOne()
    {
        $product = (new Product())->setSku('testsku');
        $newCategory = $this->createMock(Category::class);
        $newCategory->expects($this->once())
            ->method('addProduct');
        $currentCategory = $this->createMock(Category::class);

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(ContextInterface::class);

        $context->expects($this->once())
            ->method('get')
            ->willReturn($newCategory);
        $context->expects($this->once())
            ->method('getResult')
            ->willReturn($product);

        $this->repo->expects($this->once())
            ->method('findOneByProductSku')
            ->willReturn($currentCategory);
        $currentCategory->expects($this->once())
            ->method('removeProduct');
        $this->em->expects($this->exactly(2))->method('flush');

        $this->saveCategoryForProduct->process($context);
    }
}
