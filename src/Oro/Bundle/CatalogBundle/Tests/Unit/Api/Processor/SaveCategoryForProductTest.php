<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CatalogBundle\Api\Processor\RemoveCategoryFromProductRequest;
use Oro\Bundle\CatalogBundle\Api\Processor\SaveCategoryForProduct;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ProductBundle\Entity\Product;

class SaveCategoryForProductTest extends FormProcessorTestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var SaveCategoryForProduct
     */
    protected $processor;

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
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->repo = $this->createMock(CategoryRepository::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repo);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->processor = new SaveCategoryForProduct($this->doctrineHelper);
    }

    public function testProcessShouldBeIgnored()
    {
        $this->repo->expects(self::never())
            ->method('findOneByProductSku');
        $this->em->expects(self::never())
            ->method('flush');

        $this->processor->process($this->context);
    }

    public function testProcessShouldRemoveOldCategoryAndAddNewOne()
    {
        $product = (new Product())->setSku('testsku');
        $newCategory = $this->createMock(Category::class);
        $newCategory->expects(self::once())
            ->method('addProduct');
        $currentCategory = $this->createMock(Category::class);

        $this->repo->expects(self::once())
            ->method('findOneByProductSku')
            ->willReturn($currentCategory);
        $currentCategory->expects(self::once())
            ->method('removeProduct');
        $this->em->expects(self::exactly(2))
            ->method('flush');

        $this->context->set(RemoveCategoryFromProductRequest::CATEGORY, $newCategory);
        $this->context->setResult($product);
        $this->processor->process($this->context);
    }
}
