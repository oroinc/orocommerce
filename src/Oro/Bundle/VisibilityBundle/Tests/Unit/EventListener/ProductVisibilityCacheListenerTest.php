<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\EventListener\ProductVisibilityCacheListener;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductVisibilityCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var ResolvedProductVisibilityProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resolvedProductVisibilityProvider;

    /**
     * @var ProductVisibilityCacheListener
     */
    private $listener;

    public function setUp()
    {
        $this->cache = $this->createMock(CacheProvider::class);
        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);

        $this->listener = new ProductVisibilityCacheListener($this->cache);
    }

    public function testClearCacheOnFlush()
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        /** @var Product $firstProduct */
        $firstProduct = $this->getEntity(Product::class, ['id' => 1]);
        /** @var Product $secondProduct */
        $secondProduct = $this->getEntity(Product::class, ['id' => 2]);
        $scope = new Scope();

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([
                $firstProduct,
                new ProductVisibilityResolved($scope, $firstProduct)
            ]);

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([
                new CustomerGroupProductVisibilityResolved($scope, $firstProduct),
                new CustomerProductVisibilityResolved($scope, $secondProduct)
            ]);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->cache->expects($this->at(0))
            ->method('delete')
            ->with('Oro\Bundle\ProductBundle\Entity\Product_1');

        $this->cache->expects($this->at(1))
            ->method('delete')
            ->with('Oro\Bundle\ProductBundle\Entity\Product_2');

        $args = new OnFlushEventArgs($em);

        $this->listener->onFlush($args);
    }

    public function testClearCacheOnFlushWithVisibilityProvider(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        /** @var Product $firstProduct */
        $firstProduct = $this->getEntity(Product::class, ['id' => 1]);
        /** @var Product $secondProduct */
        $secondProduct = $this->getEntity(Product::class, ['id' => 2]);
        $scope = new Scope();

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([
                $firstProduct,
                new ProductVisibilityResolved($scope, $firstProduct)
            ]);

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([
                new CustomerGroupProductVisibilityResolved($scope, $firstProduct),
                new CustomerProductVisibilityResolved($scope, $secondProduct)
            ]);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->resolvedProductVisibilityProvider->expects($this->at(0))
            ->method('clearCache')
            ->with(1);

        $this->resolvedProductVisibilityProvider->expects($this->at(1))
            ->method('clearCache')
            ->with(2);

        $args = new OnFlushEventArgs($em);

        $this->listener->setResolvedProductVisibilityProvider($this->resolvedProductVisibilityProvider);
        $this->listener->onFlush($args);
    }

    public function testWithoutClearCacheOnFlush()
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new \stdClass()]);

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->cache->expects($this->never())
            ->method('delete');

        $args = new OnFlushEventArgs($em);

        $this->listener->onFlush($args);
    }

    public function testWithoutClearCacheOnFlushWithVisibilityProvider(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new \stdClass()]);

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->resolvedProductVisibilityProvider->expects($this->never())
            ->method('clearCache');

        $args = new OnFlushEventArgs($em);

        $this->listener->setResolvedProductVisibilityProvider($this->resolvedProductVisibilityProvider);
        $this->listener->onFlush($args);
    }
}
