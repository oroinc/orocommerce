<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Search;

use Doctrine\ORM\Query;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
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
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([
            LoadProductData::class,
            LoadProductVisibilityData::class,
        ]);

        $this->getContainer()->get('oro_customer.visibility.cache.product.cache_builder')->buildCache();
        $this->getContainer()->get('oro_website_search.indexer')->reindex(Product::class);
    }

    public function testFindOne()
    {
        $exampleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var $product \Oro\Bundle\SearchBundle\Query\Result\Item */
        $product = $this->client->getContainer()->get('oro_product.website_search.repository.product')->findOne(
            $exampleProduct->getId()
        );

        $this->assertNotNull($product);
        $this->assertEquals($product->getSelectedData()['product_id'], $exampleProduct->getId());
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->localizationHelper = $this->getContainer()->get('oro_locale.helper.localization');

        $this->loadFixtures([
            LoadProductVisibilityData::class
        ]);

        $NotFoundProduct = $this->client->getContainer()->get('oro_product.website_search.repository.product')->findOne(
            1024
        );
        $this->assertNull($NotFoundProduct);

        $this->getContainer()->get('oro_customer.visibility.cache.product.cache_builder')->buildCache();
        $this->getContainer()->get('oro_website_search.indexer')->reindex(Product::class);
    }

    public function testSearchFilteredBySkus()
    {
        $this->getContainer()->get('oro_customer.visibility.cache.product.cache_builder')->buildCache();
        $this->getContainer()->get('oro_website_search.indexer')->reindex(Product::class);
        /** @var ProductRepository $ormRepository */
        $ormRepository = $this->client->getContainer()
            ->get('doctrine')
            ->getRepository(Product::class);
        /** @var ProductSearchRepository $searchRepository */
        $searchRepository = $this->client->getContainer()
            ->get('oro_product.website_search.repository.product');

        /** @var Product[] $productsFromOrm */
        $productsFromOrm = $ormRepository->createQueryBuilder('p')
            ->setMaxResults(3)
            ->orderBy('p.id', 'desc')
            ->getQuery()
            ->getResult(Query::HYDRATE_OBJECT);

        $titleField = sprintf('title_%d', $this->localizationHelper->getCurrentLocalization()->getId());
        $skus       = [];
        foreach ($productsFromOrm as $productFromOrm) {
            $skus[] = $productFromOrm->getSku();
        }

        $productsFromSearch = $searchRepository->searchFilteredBySkus($skus);
        $this->assertEquals(count($productsFromOrm), count($productsFromSearch));

        foreach ($productsFromOrm as $productFromOrm) {
            $found           = $foundTitle = false;
            $ormProductTitle = (string)$productFromOrm->getNames()[0];
            foreach ($productsFromSearch as $productFromSearch) {
                if ($productFromSearch->getSelectedData()['sku'] === $productFromOrm->getSku()) {
                    $found = true;
                }
                if ($productFromSearch->getSelectedData()[$titleField] === $ormProductTitle) {
                    $foundTitle = true;
                }
            }
            $this->assertTrue($found, 'Product with sku `' . $productFromOrm->getSku() . '` not found.');
            $this->assertTrue($foundTitle, 'Product title `' . $ormProductTitle . '` not present.');
        }
    }
}
