<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem;

use Doctrine\ORM\EntityManager;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractFinderDatabaseStrategyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FinderStrategyInterface
     */
    protected $strategy;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var AbstractRelatedItemConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->repository = $this->createRepositoryMock();
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configProvider = $this->createMock(AbstractRelatedItemConfigProvider::class);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repository);

        $this->strategy = $this->createFinderStrategy();
    }

    /**
     * @return FinderStrategyInterface
     */
    abstract public function createFinderStrategy();

    /**
     * @return EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    abstract public function createRepositoryMock();

    /**
     * @return FinderStrategyInterface
     */
    protected function getFinderStrategy()
    {
        return $this->strategy;
    }

    protected function doctrineHelperShouldNotBeAskedForRepository()
    {
        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityManager')
            ->with(Product::class);
    }

    protected function relatedItemsFunctionalityShouldBeEnabled()
    {
        $this->configProvider
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
    }

    protected function relatedItemsFunctionalityShouldBeDisabled()
    {
        $this->configProvider
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(false);
    }

    protected function andShouldNotBeBidirectional()
    {
        $this->configProvider
            ->expects($this->any())
            ->method('isBidirectional')
            ->willReturn(false);
    }

    /**
     * @param array $properties
     * @return Product
     */
    protected function getProduct(array $properties = [])
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, $properties);

        return $product;
    }
}
