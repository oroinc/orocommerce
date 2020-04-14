<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\CheckoutBundle\EventListener\ProductAvailabilityCacheListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Doctrine\EntityManagerMockBuilder;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductAvailabilityCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var ProductAvailabilityCacheListener
     */
    private $productAvailabilityCacheListener;

    /**
     * @var EntityManagerMockBuilder
     */
    private $entityManagerMockBuilder;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheProvider::class);
        $this->entityManagerMockBuilder = new EntityManagerMockBuilder();

        $this->productAvailabilityCacheListener = new ProductAvailabilityCacheListener($this->cache);
    }

    public function testOnFlush()
    {
        /** @var Product $firstProduct */
        $firstProduct = $this->getEntity(Product::class, ['id' => 1]);
        /** @var Product $secondProduct */
        $secondProduct = $this->getEntity(Product::class, ['id' => 2]);

        $entityManager = $this->entityManagerMockBuilder->getEntityManager(
            $this,
            [$firstProduct],
            [$secondProduct],
            [$firstProduct]
        );

        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$firstProduct->getId()],
                [$secondProduct->getId()]
            )
            ->willReturn(true);

        $this->productAvailabilityCacheListener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushNotProducts()
    {
        /** @var Product $firstProduct */
        $productWithoutId = $this->getEntity(Product::class);
        $entityManager = $this->entityManagerMockBuilder->getEntityManager(
            $this,
            [$productWithoutId],
            [new \stdClass()],
            [new \stdClass()]
        );

        $this->cache->expects($this->never())
            ->method('delete');

        $this->productAvailabilityCacheListener->onFlush(new OnFlushEventArgs($entityManager));
    }
}
