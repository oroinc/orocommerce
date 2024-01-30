<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductKitRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductKitData::class,
        ]);
    }

    private function getRepository(): ProductRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(Product::class);
    }

    public function testGetProductKitsByRequiredProduct(): void
    {
        self::assertCount(3, $this->getRepository()->getProductKitsByRequiredProduct(
            $this->getReference('product-1')
        ));
    }

    public function testGetProductKitsByProductIds(): void
    {
        $product2 = $this->getReference('product-2');
        $kit2 = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        $kit3 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);

        $expected = [$kit2->getId(), $kit3->getId()];
        $actual = $this->getRepository()->getProductKitsByProductIds([$product2->getId()]);
        self::assertNotEmpty($actual);
        self::assertEqualsCanonicalizing(
            $expected,
            array_map(fn (Product $product) => $product->getId(), $actual)
        );

        self::assertEmpty(
            $this->getRepository()->getProductKitsByProductIds([])
        );

        self::assertEmpty(
            $this->getRepository()->getProductKitsByProductIds([PHP_INT_MAX, PHP_INT_MAX-1])
        );
    }

    public function testGetProductKitIdsByProductIds(): void
    {
        $product2 = $this->getReference('product-2');
        $kit2 = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        $kit3 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);

        self::assertEqualsCanonicalizing(
            [$kit2->getId(), $kit3->getId()],
            $this->getRepository()->getProductKitIdsByProductIds([$product2->getId()])
        );

        self::assertEmpty(
            $this->getRepository()->getProductKitsByProductIds([])
        );

        self::assertEmpty(
            $this->getRepository()->getProductKitsByProductIds([PHP_INT_MAX, PHP_INT_MAX-1])
        );
    }
}
