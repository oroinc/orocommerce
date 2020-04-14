<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractFinderDatabaseStrategyTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var FinderStrategyInterface
     */
    protected $strategy;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $repository;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityManager;

    /**
     * @var AbstractRelatedItemConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configProvider;

    protected function setUp(): void
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
     * @return EntityRepository|\PHPUnit\Framework\MockObject\MockObject
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
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
    }

    protected function relatedItemsFunctionalityShouldBeDisabled()
    {
        $this->configProvider
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
    }

    protected function andShouldNotBeBidirectional()
    {
        $this->configProvider
            ->expects($this->once())
            ->method('isBidirectional')
            ->willReturn(false);
    }

    protected function configManagerBidirectionalOptionShouldBeIgnored()
    {
        $this->configProvider
            ->expects($this->never())
            ->method('isBidirectional');
    }

    protected function configManagerLimitOptionShouldBeIgnored()
    {
        $this->configProvider
            ->expects($this->never())
            ->method('getLimit');
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
