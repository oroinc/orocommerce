<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CheckoutBundle\EventListener\ProductAvailabilityCacheListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class ProductAvailabilityCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ProductAvailabilityCacheListener */
    private $productAvailabilityCacheListener;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(AbstractAdapter::class);

        $this->productAvailabilityCacheListener = new ProductAvailabilityCacheListener($this->cache);
    }

    /**
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEntityManager(
        array $insertions = [],
        array $updates = [],
        array $deletions = [],
        array $entityChangeSet = []
    ) {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertions);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);
        $unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn($entityChangeSet);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $entityManager;
    }

    public function testOnFlush()
    {
        $firstProduct = new Product();
        ReflectionUtil::setId($firstProduct, 1);
        $secondProduct = new Product();
        ReflectionUtil::setId($secondProduct, 2);

        $entityManager = $this->getEntityManager(
            [$firstProduct],
            [$secondProduct],
            [$firstProduct]
        );

        $this->cache->expects($this->once())
            ->method('deleteItems')
            ->with([$firstProduct->getId(), $secondProduct->getId()])
            ->willReturn(true);

        $this->productAvailabilityCacheListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushNotProducts()
    {
        $productWithoutId = new Product();
        $entityManager = $this->getEntityManager(
            [$productWithoutId],
            [new \stdClass()],
            [new \stdClass()]
        );

        $this->cache->expects($this->never())
            ->method('delete');

        $this->productAvailabilityCacheListener->onFlush(new OnFlushEventArgs($entityManager));
    }
}
