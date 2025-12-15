<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Provider\ProductFallbackChunkProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

/**
 * @dbIsolationPerTest
 */
final class ProductFallbackChunkProviderTest extends WebTestCase
{
    private ProductFallbackChunkProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganization::class,
            LoadUser::class,
            LoadAttributeFamilyData::class,
        ]);

        $this->provider = self::getContainer()->get('oro_product.provider.fallback_chunk');
    }

    public function testGetProductIdChunksWithNoProducts(): void
    {
        $chunks = [];
        foreach ($this->provider->getProductIdChunks(10) as $chunk) {
            $chunks[] = $chunk;
        }

        self::assertEmpty($chunks);
    }

    public function testGetPendingProductCountWithNoProducts(): void
    {
        $count = $this->provider->getPendingProductCount();

        self::assertSame(0, $count);
    }

    public function testGetProductIdChunksReturnsCorrectChunks(): void
    {
        $products = $this->createProductsWithNullFallbacks(7);
        $productIds = array_map(fn ($p) => $p->getId(), $products);

        $chunkSize = 3;
        $chunks = [];
        $allIds = [];

        foreach ($this->provider->getProductIdChunks($chunkSize) as $chunk) {
            $chunks[] = $chunk;
            $allIds = array_merge($allIds, $chunk);
        }

        // Should have at least 3 chunks (7 products / 3 chunk size = 2.33 ≈ 3 chunks)
        self::assertGreaterThanOrEqual(3, count($chunks));

        // Each chunk should have at most chunkSize elements
        foreach ($chunks as $chunk) {
            self::assertLessThanOrEqual($chunkSize, count($chunk));
            self::assertNotEmpty($chunk);
        }

        // All created product IDs should be in chunks
        foreach ($productIds as $productId) {
            self::assertContains($productId, $allIds, sprintf('Product ID %d should be in chunks', $productId));
        }
    }

    public function testGetPendingProductCountReturnsCorrectCount(): void
    {
        $this->createProductsWithNullFallbacks(5);

        $count = $this->provider->getPendingProductCount();

        self::assertGreaterThanOrEqual(5, $count);
    }

    public function testGetProductIdChunksWithDifferentChunkSizes(): void
    {
        $this->createProductsWithNullFallbacks(10);

        // Test with chunk size 2
        $chunks = iterator_to_array($this->provider->getProductIdChunks(2));
        self::assertGreaterThanOrEqual(5, count($chunks));
        foreach ($chunks as $chunk) {
            self::assertLessThanOrEqual(2, count($chunk));
        }

        // Test with chunk size 5
        $chunks = iterator_to_array($this->provider->getProductIdChunks(5));
        self::assertGreaterThanOrEqual(2, count($chunks));
        foreach ($chunks as $chunk) {
            self::assertLessThanOrEqual(5, count($chunk));
        }
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
            $sku = 'TEST-FALLBACK-CHUNK-' . uniqid();

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

        // Remove EntityFieldFallbackValue objects to simulate products with null fallbacks
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
}
