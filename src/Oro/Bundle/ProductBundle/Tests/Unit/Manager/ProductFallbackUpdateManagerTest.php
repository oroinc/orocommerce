<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Manager;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackPopulator;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Bundle\ProductBundle\Provider\ProductFallbackChunkProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ProductFallbackUpdateManagerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private ProductFallbackChunkProvider&MockObject $chunkProvider;
    private ProductFallbackPopulator&MockObject $populator;
    private LoggerInterface&MockObject $logger;
    private OptionalListenerManager&MockObject $listenerManager;
    private EntityManagerInterface&MockObject $em;
    private ProductFallbackUpdateManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->chunkProvider = $this->createMock(ProductFallbackChunkProvider::class);
        $this->populator = $this->createMock(ProductFallbackPopulator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->manager = new ProductFallbackUpdateManager(
            $this->doctrine,
            $this->chunkProvider,
            $this->populator,
            $this->logger,
            $this->listenerManager
        );
    }

    public function testProcessChunkWithEmptyArrayReturnsZeroWithoutTouchingEntityManagerOrListeners(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');
        $this->listenerManager->expects(self::never())
            ->method('disableListeners');
        $this->listenerManager->expects(self::never())
            ->method('enableListeners');
        $this->em->expects(self::never())
            ->method('clear');

        self::assertSame(0, $this->manager->processChunk([]));
    }

    public function testProcessChunkPersistsAndFlushesUpdatedProductsThenClearsEntityManager(): void
    {
        $productA = new Product();
        $productB = new Product();
        $this->expectGetProducts([1, 2], [$productA, $productB]);

        $this->populator->expects(self::exactly(2))
            ->method('populate')
            ->willReturnMap([
                [$productA, true],
                [$productB, true],
            ]);

        $this->em->expects(self::exactly(2))
            ->method('persist')
            ->with(self::logicalOr($productA, $productB));

        $this->em->expects(self::once())
            ->method('flush');
        $this->logger->expects(self::once())
            ->method('info');
        $this->logger->expects(self::never())
            ->method('error');

        $this->em->expects(self::once())
            ->method('clear');

        $this->listenerManager->expects(self::once())
            ->method('disableListeners')
            ->with([]);
        $this->listenerManager->expects(self::once())
            ->method('enableListeners')
            ->with([]);

        self::assertSame(2, $this->manager->processChunk([1, 2]));
    }

    public function testProcessChunkDoesNotFlushWhenNoProductsNeedUpdateButStillClearsEntityManager(): void
    {
        $product = new Product();
        $this->expectGetProducts([1], [$product]);

        $this->populator->expects(self::once())
            ->method('populate')
            ->with($product)
            ->willReturn(false);

        $this->em->expects(self::never())
            ->method('persist');
        $this->em->expects(self::never())
            ->method('flush');

        $this->em->expects(self::once())
            ->method('clear');
        $this->listenerManager->expects(self::once())
            ->method('enableListeners')
            ->with([]);

        self::assertSame(0, $this->manager->processChunk([1]));
    }

    public function testProcessChunkLogsAndRethrowsWhenFlushFailsAndStillClearsEntityManager(): void
    {
        $product = new Product();
        $this->expectGetProducts([1], [$product]);

        $this->populator->expects(self::once())
            ->method('populate')
            ->willReturn(true);

        $flushException = new \RuntimeException('flush failed');
        $this->em->expects(self::once())
            ->method('flush')
            ->willThrowException($flushException);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Failed to flush product fallback changes', self::arrayHasKey('exception'));
        $this->logger->expects(self::never())
            ->method('info');

        // Even though flush() failed, the identity map must still be cleared and listeners re-enabled.
        $this->em->expects(self::once())
            ->method('clear');
        $this->listenerManager->expects(self::once())
            ->method('enableListeners')
            ->with([]);

        $this->expectExceptionObject($flushException);

        $this->manager->processChunk([1]);
    }

    /**
     * @param int[] $productIds
     * @param Product[] $products
     */
    private function expectGetProducts(array $productIds, array $products): void
    {
        // processChunk() resolves the EntityManager itself and again inside getProducts().
        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($products);

        $qb = $this->createMock(QueryBuilder::class);
        $exprFunc = $this->createMock(Expr\Func::class);

        $expr = $this->createMock(Expr::class);
        $expr->expects(self::once())
            ->method('in')
            ->with('p.id', ':ids')
            ->willReturn($exprFunc);

        $qb->expects(self::once())
            ->method('expr')
            ->willReturn($expr);
        $qb->expects(self::once())
            ->method('where')
            ->with($exprFunc)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('ids', $productIds)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repo);
    }
}
