<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Search;

use Doctrine\ORM\Query;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListForDefaultWebsite;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MysqlVersionCheckTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class ProductRepositoryTest extends WebTestCase
{
    use MysqlVersionCheckTrait;
    use ConfigManagerAwareTestTrait;

    /** @var ConfigManager */
    private $configManager;

    /** @var string */
    private $configKey;

    /** @var bool */
    private $originalConfigValue;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([
            LoadFrontendProductData::class,
            LoadCombinedPriceListForDefaultWebsite::class
        ]);

        $this->configManager = self::getConfigManager('global');

        $this->configKey = Configuration::getConfigKeyByName(Configuration::ALLOW_PARTIAL_PRODUCT_SEARCH);
        $this->originalConfigValue = $this->configManager->get($this->configKey);

        $this->platform = $this->getContainer()->get('doctrine')->getManager()->getConnection()->getDatabasePlatform();
    }

    protected function tearDown(): void
    {
        if ($this->configManager->get($this->configKey) === $this->originalConfigValue) {
            return;
        }

        $this->configManager->set($this->configKey, $this->originalConfigValue);
        $this->configManager->flush();
    }

    public function testGetFamilyAttributeCountsQuery()
    {
        $query = $this->client->getContainer()
            ->get('oro_product.website_search.repository.product')
            ->getFamilyAttributeCountsQuery(null, 'familyAttributesCount');

        $expectedResult = [
            $this->getReference(LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE)->getId() => 6,
            $this->getReference('attribute_family_1')->getId() => 1
        ];

        ksort($expectedResult);

        /**
         * The result takes into account products that have stock status - "in_stock" or "out_of_stock"
         * and status "enabled".
         * Conditions with stock status and status populated within BeforeSearchEvent to the query.
         */
        $aggregateData = $query->getResult()->getAggregatedData();

        $this->assertArrayHasKey('familyAttributesCount', $aggregateData);

        $actualResult = $aggregateData['familyAttributesCount'];
        ksort($actualResult);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testFindOne()
    {
        $exampleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var \Oro\Bundle\SearchBundle\Query\Result\Item $product */
        $product = $this->client->getContainer()->get('oro_product.website_search.repository.product')->findOne(
            $exampleProduct->getId()
        );

        $this->assertNotNull($product);
        $this->assertEquals($product->getId(), $exampleProduct->getId());
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $notFoundProduct = $this->client->getContainer()->get('oro_product.website_search.repository.product')->findOne(
            100500000
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
        $this->assertCount(count($productsFromOrm), $productsFromSearch);

        foreach ($productsFromOrm as $productFromOrm) {
            $found = false;
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

    public function testFindBySkuOrName()
    {
        if ($this->isMysqlPlatform() && $this->isInnoDBFulltextIndexSupported()) {
            $this->configManager->set($this->configKey, true);
            $this->configManager->flush();
        }

        /** @var ProductRepository $ormRepository */
        $ormRepository = $this->client->getContainer()
            ->get('doctrine')
            ->getRepository(Product::class);

        /** @var ProductSearchRepository $searchRepository */
        $searchRepository = $this->client->getContainer()
            ->get('oro_product.website_search.repository.product');

        $productsFromOrm = $ormRepository->createQueryBuilder('p')
            ->where('p.sku = :sku')
            ->setParameter('sku', LoadProductData::PRODUCT_7)
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $productsFromSearch = $searchRepository->findBySkuOrName(LoadProductData::PRODUCT_7);

        $this->assertTrue(count($productsFromSearch) > 0);

        foreach ($productsFromOrm as $productFromOrm) {
            $found = false;

            foreach ($productsFromSearch as $productFromSearch) {
                if ($productFromSearch->getSelectedData()['sku'] === $productFromOrm['sku']) {
                    $found = true;
                }
            }

            $this->assertTrue($found, 'Product with sku `' . $productFromOrm['sku'] . '` not found.');
        }
    }

    /**
     * @dataProvider productSearchOperatorDataProvider
     */
    public function testGetSearchQueryBySkuOrName(bool $isConfigEnabled): void
    {
        if (!$isConfigEnabled && $this->isMysqlPlatform() && $this->isInnoDBFulltextIndexSupported()) {
            self::markTestSkipped(
                'Skipped because current test implementation isn\'t compatible with InnoDB Full-Text index'
            );
        }

        $this->configManager->set($this->configKey, $isConfigEnabled);
        $this->configManager->flush();

        /** @var ProductRepository $ormRepository */
        $ormRepository = $this->client->getContainer()
            ->get('doctrine')
            ->getRepository(Product::class);

        /** @var ProductSearchRepository $searchRepository */
        $searchRepository = $this->client->getContainer()
            ->get('oro_product.website_search.repository.product');

        $productsFromOrm = $ormRepository->createQueryBuilder('p')
            ->where('p.sku = :sku')
            ->setParameter('sku', LoadProductData::PRODUCT_6)
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $productsFromSearch = $searchRepository
            ->getSearchQueryBySkuOrName(substr(LoadProductData::PRODUCT_6, 0, 5))
            ->getResult()
            ->getElements();

        $this->assertTrue(count($productsFromSearch) > 0);

        foreach ($productsFromOrm as $productFromOrm) {
            $found = false;

            foreach ($productsFromSearch as $productFromSearch) {
                if ($productFromSearch->getSelectedData()['sku'] === $productFromOrm['sku']) {
                    $found = true;
                }
            }

            $this->assertTrue($found, 'Product with sku `' . $productFromOrm['sku'] . '` not found.');
        }
    }

    /**
     * @dataProvider productSearchOperatorDataProvider
     */
    public function testGetAutocompleteSearchQuery(bool $isConfigEnabled): void
    {
        if (!$isConfigEnabled && $this->isMysqlPlatform() && $this->isInnoDBFulltextIndexSupported()) {
            self::markTestSkipped(
                'Skipped because current test implementation isn\'t compatible with InnoDB Full-Text index'
            );
        }

        $this->configManager->set($this->configKey, $isConfigEnabled);
        $this->configManager->flush();

        /** @var ProductRepository $ormRepository */
        $ormRepository = $this->client->getContainer()
            ->get('doctrine')
            ->getRepository(Product::class);

        /** @var ProductSearchRepository $searchRepository */
        $searchRepository = $this->client->getContainer()
            ->get('oro_product.website_search.repository.product');

        $productsFromOrm = $ormRepository->createQueryBuilder('p')
            ->where('p.sku IN (:sku)')
            ->setParameter('sku', [LoadProductData::PRODUCT_7, LoadProductData::PRODUCT_9])
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $productsFromSearch = $searchRepository
            ->getAutocompleteSearchQuery('продукт', 2)
            ->getResult()
            ->getElements();

        $this->assertTrue(count($productsFromSearch) > 0);

        foreach ($productsFromOrm as $productFromOrm) {
            $found = false;

            foreach ($productsFromSearch as $productFromSearch) {
                if ($productFromSearch->getSelectedData()['sku'] === $productFromOrm['sku']) {
                    $found = true;
                }
            }

            $this->assertTrue($found, 'Product with sku `' . $productFromOrm['sku'] . '` not found.');
        }
    }

    /**
     * @dataProvider productSearchOperatorDataProvider
     */
    public function testGetProductSearchOperator(bool $isConfigEnabled, string $expected): void
    {
        if (!$isConfigEnabled && $this->isMysqlPlatform() && $this->isInnoDBFulltextIndexSupported()) {
            self::markTestSkipped(
                'Skipped because current test implementation isn\'t compatible with InnoDB Full-Text index'
            );
        }

        $this->configManager->set($this->configKey, $isConfigEnabled);
        $this->configManager->flush();

        $repository = $this->client->getContainer()->get('oro_product.website_search.repository.product');

        $this->assertEquals($expected, $repository->getProductSearchOperator());
    }

    /**
     * @return array[]
     */
    public function productSearchOperatorDataProvider(): array
    {
        return [
            [
                'isConfigEnabled' => false,
                'expected' => 'contains',
            ],
            [
                'isConfigEnabled' => true,
                'expected' => 'like',
            ],
        ];
    }
}
