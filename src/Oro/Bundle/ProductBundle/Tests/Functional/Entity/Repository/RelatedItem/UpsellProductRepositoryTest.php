<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\UpsellProductRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadUpsellProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class UpsellProductRepositoryTest extends WebTestCase
{
    private UpsellProductRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadUpsellProductData::class]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(UpsellProduct::class);
    }

    private function getProductBySku(string $sku): Product
    {
        return self::getContainer()->get('doctrine')->getRepository(Product::class)->findOneBy(['sku' => $sku]);
    }

    public function testExistsReturnTrue()
    {
        $product3 = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $product1 = $this->getProductBySku(LoadProductData::PRODUCT_1);
        $this->assertTrue($this->repository->exists($product3, $product1));
    }

    public function testExistsReturnFalse()
    {
        $product3 = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $product6 = $this->getProductBySku(LoadProductData::PRODUCT_6);
        $this->assertFalse($this->repository->exists($product3, $product6));
    }

    /**
     * @dataProvider countRelationsForProductDataProvider
     */
    public function testCountRelationsForProduct(string $productSku, int $numberOfRelations)
    {
        $product = $this->getProductBySku($productSku);
        $this->assertSame($numberOfRelations, $this->repository->countRelationsForProduct($product->getId()));
    }

    public function testFindUpsellWithLimit()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1),
        ];
        $relatedProducts = $this->repository->findUpsell($product->getId(), 1);
        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindUpsellWithoutLimit()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1),
            $this->getProductBySku(LoadProductData::PRODUCT_2),
        ];
        $relatedProducts = $this->repository->findUpsell($product->getId());
        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindUpsellIdsUnidirectionalWithLimit()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1)->getId()
        ];
        $relatedProducts = $this->repository->findUpsellIds($product->getId(), 1);
        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindUpsellIdsUnidirectionalWithoutLimit()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1)->getId(),
            $this->getProductBySku(LoadProductData::PRODUCT_2)->getId(),
        ];
        $relatedProducts = $this->repository->findUpsellIds($product->getId());
        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function countRelationsForProductDataProvider(): array
    {
        return [
            ['product-1', 0],
            ['product-3', 2],
            ['product-4', 1],
        ];
    }
}
