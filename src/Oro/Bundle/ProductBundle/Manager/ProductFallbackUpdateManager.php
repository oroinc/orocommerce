<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductFallbackChunkProvider;
use Psr\Log\LoggerInterface;

/**
 * Provides utilities for detecting and updating product fallback fields in chunks.
 * Raw SQL is avoided because each fallback row requires a generated identifier that must be
 * associated with a specific product field, therefore chunked ORM updates provide a safe compromise.
 */
class ProductFallbackUpdateManager
{
    /**
     * @var array
     */
    protected $listeners = [];

    public function __construct(
        private ManagerRegistry $doctrine,
        private ProductFallbackChunkProvider $chunkProvider,
        private ProductFallbackPopulator $populator,
        private LoggerInterface $logger,
        private OptionalListenerManager $listenerManager
    ) {
    }

    /**
     * Add listener to be disabled during fallback updates
     *
     * @param string $listener
     */
    public function disableListener(string $listener): void
    {
        $this->listeners[] = $listener;
    }

    /**
     * @return iterable<int[]>
     */
    public function getProductIdChunks(int $chunkSize): iterable
    {
        if ($chunkSize <= 0) {
            throw new \InvalidArgumentException('Chunk size must be a positive integer.');
        }

        yield from $this->chunkProvider->getProductIdChunks($chunkSize);
    }

    public function processChunk(array $productIds): int
    {
        if (!$productIds) {
            return 0;
        }

        $this->listenerManager->disableListeners($this->listeners);

        try {
            $em = $this->getEntityManager();
            $products = $this->getProducts($productIds);
            $updatedProducts = 0;

            foreach ($products as $product) {
                if ($this->populator->populate($product)) {
                    $em->persist($product);
                    $updatedProducts++;
                }
            }

            if ($updatedProducts > 0) {
                try {
                    $em->flush();
                    $this->logger->info(
                        'Product fallback chunk processed successfully',
                        ['updated_count' => $updatedProducts, 'chunk_size' => count($productIds)]
                    );
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Failed to flush product fallback changes',
                        ['exception' => $e, 'product_ids' => $productIds]
                    );
                    throw $e;
                }
            }

            // Detach processed products to free memory without clearing the entire EM
            foreach ($products as $product) {
                $em->detach($product);
            }

            return $updatedProducts;
        } finally {
            $this->listenerManager->enableListeners($this->listeners);
        }
    }

    public function getPendingProductCount(): int
    {
        return $this->chunkProvider->getPendingProductCount();
    }

    public function hasPendingProducts(): bool
    {
        return $this->chunkProvider->getPendingProductCount() > 0;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(Product::class);
    }

    /**
     * @return Product[]
     */
    private function getProducts(array $ids): array
    {
        $repo = $this->getEntityManager()->getRepository(Product::class);

        return $repo->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
