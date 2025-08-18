<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Search;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
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

    private ?bool $initialAllowPartialSearch;
    private AbstractPlatform $platform;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        self::getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([LoadFrontendProductData::class]);

        $configManager = self::getConfigManager();
        $this->initialAllowPartialSearch = $configManager->get('oro_product.allow_partial_product_search');

        $this->platform = self::getContainer()->get('doctrine')->getManager()->getConnection()->getDatabasePlatform();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        if ($configManager->get('oro_product.allow_partial_product_search') !== $this->initialAllowPartialSearch) {
            $configManager->set('oro_product.allow_partial_product_search', $this->initialAllowPartialSearch);
            $configManager->flush();
        }
    }

    private function getProductSearchRepository(): ProductSearchRepository
    {
        return self::getContainer()->get('oro_product.website_search.repository.product');
    }

    private function getProductRepository(): ProductRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Product::class);
    }

    public function testGetFamilyAttributeCountsQuery(): void
    {
        $query = $this->getProductSearchRepository()->getFamilyAttributeCountsQuery();

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

        self::assertArrayHasKey('familyAttributesCount', $aggregateData);

        $actualResult = $aggregateData['familyAttributesCount'];
        ksort($actualResult);

        self::assertEquals($expectedResult, $actualResult);
    }

    public function testFindOne(): void
    {
        $exampleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        $product = $this->getProductSearchRepository()->findOne($exampleProduct->getId());

        self::assertNotNull($product);
        self::assertEquals($product->getId(), $exampleProduct->getId());
        $this->initClient();
        self::getContainer()->get('request_stack')->push(Request::create(''));

        self::assertNull($this->getProductSearchRepository()->findOne(100500000));
    }

    public function testSearchFilteredBySkus(): void
    {
        $productsFromOrm = $this->getProductRepository()->createQueryBuilder('p')
            ->setMaxResults(3)
            ->orderBy('p.id', 'desc')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $skus = [];
        foreach ($productsFromOrm as $productFromOrm) {
            $skus[] = $productFromOrm['sku'];
        }

        $productsFromSearch = $this->getProductSearchRepository()->searchFilteredBySkus($skus);
        self::assertCount(count($productsFromOrm), $productsFromSearch);

        foreach ($productsFromOrm as $productFromOrm) {
            $found = false;
            foreach ($productsFromSearch as $productFromSearch) {
                if ($productFromSearch->getSelectedData()['sku'] === $productFromOrm['sku']) {
                    $found = true;
                }
            }
            self::assertTrue($found, 'Product with sku `' . $productFromOrm['sku'] . '` not found.');
        }
    }

    public function testGetSearchQuery(): void
    {
        /** @var Product $exampleProduct */
        $exampleProduct = $this->getReference(LoadProductData::PRODUCT_1);
        $products = $this->getProductSearchRepository()->getSearchQuery($exampleProduct->getSku(), 0, 1)->getResult();

        foreach ($products->getElements() as $productItem) {
            self::assertArrayHasKey('sku', $productItem->getSelectedData());
            self::assertEquals(LoadProductData::PRODUCT_1, $productItem->getSelectedData()['sku']);
            self::assertArrayHasKey('name', $productItem->getSelectedData());
        }
    }

    public function testFindBySkuOrName(): void
    {
        if ($this->isMysqlPlatform() && $this->isInnoDBFulltextIndexSupported()) {
            $configManager = self::getConfigManager();
            $configManager->set('oro_product.allow_partial_product_search', true);
            $configManager->flush();
        }

        $productsFromOrm = $this->getProductRepository()->createQueryBuilder('p')
            ->where('p.sku = :sku')
            ->setParameter('sku', LoadProductData::PRODUCT_7)
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $productsFromSearch = $this->getProductSearchRepository()->findBySkuOrName(LoadProductData::PRODUCT_7);

        self::assertTrue(count($productsFromSearch) > 0);

        foreach ($productsFromOrm as $productFromOrm) {
            $found = false;
            foreach ($productsFromSearch as $productFromSearch) {
                if ($productFromSearch->getSelectedData()['sku'] === $productFromOrm['sku']) {
                    $found = true;
                }
            }
            self::assertTrue($found, 'Product with sku `' . $productFromOrm['sku'] . '` not found.');
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

        $configManager = self::getConfigManager();
        $configManager->set('oro_product.allow_partial_product_search', $isConfigEnabled);
        $configManager->flush();

        $productsFromOrm = $this->getProductRepository()->createQueryBuilder('p')
            ->where('p.sku = :sku')
            ->setParameter('sku', LoadProductData::PRODUCT_6)
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $productsFromSearch = $this->getProductSearchRepository()
            ->getSearchQueryBySkuOrName(substr(LoadProductData::PRODUCT_6, 0, 5))
            ->getResult()
            ->getElements();

        self::assertTrue(count($productsFromSearch) > 0);

        foreach ($productsFromOrm as $productFromOrm) {
            $found = false;
            foreach ($productsFromSearch as $productFromSearch) {
                if ($productFromSearch->getSelectedData()['sku'] === $productFromOrm['sku']) {
                    $found = true;
                }
            }
            self::assertTrue($found, 'Product with sku `' . $productFromOrm['sku'] . '` not found.');
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

        $configManager = self::getConfigManager();
        $configManager->set('oro_product.allow_partial_product_search', $isConfigEnabled);
        $configManager->flush();

        $productsFromOrm = $this->getProductRepository()->createQueryBuilder('p')
            ->where('p.sku IN (:sku)')
            ->setParameter('sku', [LoadProductData::PRODUCT_7, LoadProductData::PRODUCT_9])
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $productsFromSearch = $this->getProductSearchRepository()
            ->getAutocompleteSearchQuery('продукт', 2)
            ->getResult()
            ->getElements();

        self::assertTrue(count($productsFromSearch) > 0);

        foreach ($productsFromOrm as $productFromOrm) {
            $found = false;
            foreach ($productsFromSearch as $productFromSearch) {
                if ($productFromSearch->getSelectedData()['sku'] === $productFromOrm['sku']) {
                    $found = true;
                }
            }
            self::assertTrue($found, 'Product with sku `' . $productFromOrm['sku'] . '` not found.');
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

        $configManager = self::getConfigManager();
        $configManager->set('oro_product.allow_partial_product_search', $isConfigEnabled);
        $configManager->flush();

        self::assertEquals($expected, $this->getProductSearchRepository()->getProductSearchOperator());
    }

    public function productSearchOperatorDataProvider(): array
    {
        return [
            [
                'isConfigEnabled' => false,
                'expected' => 'contains'
            ],
            [
                'isConfigEnabled' => true,
                'expected' => 'like'
            ]
        ];
    }
}
