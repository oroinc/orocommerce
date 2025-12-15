<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Manager;

use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

/**
 * @dbIsolationPerTest
 */
class ProductFallbackUpdateManagerTest extends WebTestCase
{
    private ProductFallbackUpdateManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganization::class,
            LoadUser::class,
            LoadAttributeFamilyData::class,
        ]);

        $this->manager = self::getContainer()->get('oro_product.manager.fallback_update');
    }

    public function testGetProductIdChunksThrowsExceptionForZeroChunkSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chunk size must be a positive integer.');

        iterator_to_array($this->manager->getProductIdChunks(0));
    }

    public function testGetProductIdChunksThrowsExceptionForNegativeChunkSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chunk size must be a positive integer.');

        iterator_to_array($this->manager->getProductIdChunks(-10));
    }

    public function testProcessChunkUpdatesProductsInDatabase(): void
    {
        // Create products with null fallback values
        $products = $this->createProductsWithNullFallbacks(5);
        $productIds = array_map(fn ($p) => $p->getId(), $products);

        // Verify products have null pageTemplate before processing
        foreach ($products as $product) {
            $freshProduct = $this->getProductById($product->getId());
            self::assertNull($freshProduct->getPageTemplate(), 'Product should have null pageTemplate initially');
        }

        $updatedCount = $this->manager->processChunk($productIds);
        self::assertSame(5, $updatedCount, 'All 5 products should be updated');

        foreach ($productIds as $productId) {
            $updatedProduct = $this->getProductById($productId);
            $pageTemplate = $updatedProduct->getPageTemplate();

            self::assertNotNull($pageTemplate, 'Product should have pageTemplate after processing');
            self::assertNotNull($pageTemplate->getFallback(), 'PageTemplate should have fallback value set');
        }
    }

    public function testProcessChunkReturnsZeroWhenNoProductsNeedUpdate(): void
    {
        // Create products and process them once to fill fallbacks
        $products = $this->createProductsWithNullFallbacks(3);
        $productIds = array_map(fn ($p) => $p->getId(), $products);

        $this->manager->processChunk($productIds);

        // Process again - should return 0 as all products already have fallbacks
        $updatedCount = $this->manager->processChunk($productIds);

        self::assertSame(0, $updatedCount, 'Should return 0 when no products need update');
    }

    public function testProcessChunkWithPartialUpdates(): void
    {
        // Create 5 products with null fallbacks
        $products = $this->createProductsWithNullFallbacks(5);
        $productIds = array_map(fn ($p) => $p->getId(), $products);

        // Process first 3 products
        $firstBatch = array_slice($productIds, 0, 3);
        $updatedCount = $this->manager->processChunk($firstBatch);
        self::assertSame(3, $updatedCount);

        // Process all 5 products - only last 2 should be updated
        $updatedCount = $this->manager->processChunk($productIds);
        self::assertSame(2, $updatedCount);
    }

    public function testProcessChunkWithNonExistentProductIds(): void
    {
        $nonExistentIds = [999999, 999998, 999997];

        $result = $this->manager->processChunk($nonExistentIds);

        self::assertSame(0, $result);
    }

    public function testGetProductIdChunksReturnsChunksFromDatabase(): void
    {
        $createdProducts = $this->createProductsWithNullFallbacks(10);
        $createdIds = array_map(fn ($p) => $p->getId(), $createdProducts);

        $chunks = [];
        $allIds = [];
        foreach ($this->manager->getProductIdChunks(3) as $chunk) {
            $chunks[] = $chunk;
            $allIds = array_merge($allIds, $chunk);
        }

        // Should have at least 4 chunks (10 products / 3 chunk size = 3.33 ≈ 4 chunks)
        self::assertGreaterThanOrEqual(4, count($chunks));

        // Each chunk (except possibly last) should have at most 3 elements
        foreach ($chunks as $chunk) {
            self::assertLessThanOrEqual(3, count($chunk));
        }

        // All created product IDs should be in chunks
        foreach ($createdIds as $createdId) {
            self::assertContains($createdId, $allIds, sprintf('Product ID %d should be in chunks', $createdId));
        }
    }

    public function testGetPendingProductCountReturnsCorrectCount(): void
    {
        $this->createProductsWithNullFallbacks(7);

        $count = $this->manager->getPendingProductCount();

        self::assertGreaterThanOrEqual(7, $count, 'Should count at least 7 products with null fallbacks');
    }

    public function testHasPendingProductsReturnsTrueWhenProductsExist(): void
    {
        $this->createProductsWithNullFallbacks(3);

        $result = $this->manager->hasPendingProducts();

        self::assertTrue($result);
    }

    /**
     * @return Product[]
     */
    private function createProductsWithNullFallbacks(int $count): array
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $user = $this->getReference(LoadUser::USER);
        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $productIds = [];

        for ($i = 0; $i < $count; $i++) {
            $product = new Product();
            $sku = 'TEST-FALLBACK-MGR-' . uniqid();

            $name = new ProductName();
            $name->setString('Test Product ' . $sku);

            $product->setSku($sku);
            $product->addName($name);
            $product->setOrganization($organization);
            $product->setOwner($user->getOwner());
            $product->setAttributeFamily($attributeFamily);
            $product->setStatus(Product::STATUS_ENABLED);

            $em->persist($product);
            $productIds[] = $product;
        }

        $em->flush();

        foreach ($productIds as $product) {
            $pageTemplate = $product->getPageTemplate();
            if ($pageTemplate) {
                $product->setPageTemplate(null);
                $em->remove($pageTemplate);
            }
        }

        $em->flush();
        $em->clear();

        $products = [];
        foreach ($productIds as $productEntity) {
            $product = $em->getRepository(Product::class)->find($productEntity->getId());
            if ($product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    private function getProductById(int $id): Product
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
        $em->clear();

        return $em->getRepository(Product::class)->find($id);
    }
}
