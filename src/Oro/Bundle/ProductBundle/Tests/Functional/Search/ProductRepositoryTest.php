<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Search;

use Doctrine\ORM\Query;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([
            LoadFrontendProductData::class,
            LoadProductVisibilityData::class,
        ]);
    }

    public function testFindOne()
    {
        $exampleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var $product \Oro\Bundle\SearchBundle\Query\Result\Item */
        $product = $this->client->getContainer()->get('oro_product.website_search.repository.product')->findOne(
            $exampleProduct->getId()
        );

        $this->assertNotNull($product);
        $this->assertEquals($product->getId(), $exampleProduct->getId());
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->loadFixtures([
            LoadProductVisibilityData::class
        ]);

        $notFoundProduct = $this->client->getContainer()->get('oro_product.website_search.repository.product')->findOne(
            1024
        );
        $this->assertNull($notFoundProduct);
    }

    public function testSearchFilteredBySkus()
    {
        /** @var ProductRepository $ormRepository */
        $ormRepository = $this->client->getContainer()
            ->get('doctrine')
            ->getRepository(Product::class);
        /** @var ProductSearchRepository $searchRepository */
        $searchRepository = $this->client->getContainer()
            ->get('oro_product.website_search.repository.product');

        $productsFromOrm = $ormRepository->createQueryBuilder('p')
            ->setMaxResults(3)
            ->orderBy('p.id', 'desc')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $skus = [];
        foreach ($productsFromOrm as $productFromOrm) {
            $skus[] = $productFromOrm['sku'];
        }

        $productsFromSearch = $searchRepository->searchFilteredBySkus($skus);
        $this->assertEquals(count($productsFromOrm), count($productsFromSearch));

        foreach ($productsFromOrm as $productFromOrm) {
            $found = $foundName = false;
            foreach ($productsFromSearch as $productFromSearch) {
                if ($productFromSearch->getSelectedData()['sku'] === $productFromOrm['sku']) {
                    $found = true;
                }
            }
            $this->assertTrue($found, 'Product with sku `' . $productFromOrm['sku'] . '` not found.');
        }
    }

    public function testGetSearchQuery()
    {
        /** @var Product $exampleProduct */
        $exampleProduct = $this->getReference(LoadProductData::PRODUCT_1);
        $products = $this->client->getContainer()->get('oro_product.website_search.repository.product')->getSearchQuery(
            $exampleProduct->getSku(),
            0,
            1
        )->getResult();

        foreach ($products->getElements() as $productItem) {
            $this->assertArrayHasKey('sku', $productItem->getSelectedData());
            $this->assertEquals(LoadProductData::PRODUCT_1, $productItem->getSelectedData()['sku']);
            $this->assertArrayHasKey('name', $productItem->getSelectedData());
        }
    }
}
