<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts as ProductFixture;

/**
 * @dbIsolation
 */
class ProductRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts']);
    }

    public function testFindOneBySku()
    {
        $this->assertNull($this->getRepository()->findOneBySku(uniqid()));

        $product = $this->getProduct(ProductFixture::PRODUCT_1);
        $expectedProduct = $this->getRepository()->findOneBySku(ProductFixture::PRODUCT_1);

        $this->assertEquals($product->getSku(), $expectedProduct->getSku());
    }

    /**
     * @param string $pattern
     * @param array $expectedSkuList
     *
     * @dataProvider patternsAndSkuListProvider
     */
    public function testFindAllSkuByPattern($pattern, $expectedSkuList)
    {
        $actualSkuList = $this->getRepository()->findAllSkuByPattern($pattern);

        $this->assertEquals($expectedSkuList, $actualSkuList);
    }

    /**
     * @return array
     */
    public function patternsAndSkuListProvider()
    {
        return [
            'exact search 1' => [ProductFixture::PRODUCT_1, [ProductFixture::PRODUCT_1]],
            'exact search 2' => [ProductFixture::PRODUCT_2, [ProductFixture::PRODUCT_2]],
            'not found' => [uniqid(), []],
            'mask all products 1' => ['product.%', [ProductFixture::PRODUCT_1, ProductFixture::PRODUCT_2]],
            'mask all products 2' => ['pro%', [ProductFixture::PRODUCT_1, ProductFixture::PRODUCT_2]],
            'product suffixed with 1' => ['%.1', [ProductFixture::PRODUCT_1]],
            'product suffixed with 2' => ['%2', [ProductFixture::PRODUCT_2]],
        ];
    }

    /**
     * @param string $reference
     * @return Product
     */
    protected function getProduct($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_product.product.class')
        );
    }
}
