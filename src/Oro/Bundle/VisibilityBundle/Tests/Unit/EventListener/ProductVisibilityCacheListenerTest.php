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
use Oro\Component\Testing\Unit\EntityTrait;

class ProductVisibilityCacheListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * @var ProductVisibilityCacheListener
     */
    private $listener;

    public function setUp()
    {
        $this->cache = $this->createMock(CacheProvider::class);
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

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
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

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->cache->expects($this->never())
            ->method('delete');

        $args = new OnFlushEventArgs($em);

        $this->listener->onFlush($args);
    }
}
