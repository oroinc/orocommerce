<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

class WebsiteSearchProductIndexerListenerTest extends WebTestCase
{
    use WebsiteSearchExtensionTrait;

    private ?EngineInterface $engine = null;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->engine = self::getSearchEngine();

        $this->loadFixtures([LoadProductKitData::class]);
    }

    public function testWebsiteSearchIndexProductKit(): void
    {
        self::reindexProductData();

        $alias = self::getIndexAlias(Product::class, [WebsiteIdPlaceholder::NAME => self::getDefaultWebsiteId()]);
        $fields = ['all_text_LOCALIZATION_ID', 'sku'];
        $expression = Criteria::expr()->eq('type', Product::TYPE_KIT);

        $products = $this->executeProductQuery($alias, $fields, $expression);
        $this->assertCount(3, $products);

        $selectedData = [];
        foreach ($products as $product) {
            $selectedData[] = $product->getSelectedData();
        }

        $expected = $this->getExpectedProductKitData();
        $sortFunction = static fn ($a, $b) => strcmp($a['sku'], $b['sku']);
        usort($expected, $sortFunction);
        usort($selectedData, $sortFunction);

        $this->assertEquals($expected, $selectedData);
    }

    private function executeProductQuery(string $alias, array $fields, ?Expression $expression = null): ?array
    {
        $query = new Query();
        $query->from($alias);
        foreach ($fields as $field) {
            $query->addSelect($field);
        }

        if ($expression) {
            $query->getCriteria()->andWhere($expression);
        }

        $result = $this->engine->search($query);
        return $result->getElements();
    }

    private function getExpectedProductKitData(): array
    {
        return [
            [
                'sku' => LoadProductKitData::PRODUCT_KIT_1,
                'all_text_1' => 'Product Kit with Single Item product-1.names.default product-1.descriptions.default '
                    . 'product-1.shortDescriptions.default PKSKU1 - Unit of Quantity Taken from product-kit-1 product-1'
            ], [
                'sku' => LoadProductKitData::PRODUCT_KIT_2,
                'all_text_1' => 'Product Kit Utilizing Sort Order product-1.names.default product-2.names.default '
                    . 'product-3.names.default product-1.descriptions.default product-2.descriptions.default '
                    . 'product-3.descriptions.default product-1.shortDescriptions.default '
                    . 'product-2.shortDescriptions.default product-3.shortDescriptions.default PKSKU2 - 1 2 '
                    . 'product-kit-2 product-1 product-2 product-3'
            ], [
                'sku' => LoadProductKitData::PRODUCT_KIT_3,
                'all_text_1' => 'Product Kit Utilizing Min and Max Quantity product-1.names.default '
                    . 'product-2.names.default product-3.names.default product-4.names.default product-5.names.default '
                    . 'product-1.descriptions.default product-2.descriptions.default product-3.descriptions.default '
                    . 'product-4.descriptions.default product-5.descriptions.default '
                    . 'product-1.shortDescriptions.default product-2.shortDescriptions.default '
                    . 'product-3.shortDescriptions.default product-4.shortDescriptions.default '
                    . 'product-5.shortDescriptions.default PKSKU3 - With product-kit-3 product-1 product-2 product-3 '
                    . 'product-4 product-5'
            ],
        ];
    }
}
