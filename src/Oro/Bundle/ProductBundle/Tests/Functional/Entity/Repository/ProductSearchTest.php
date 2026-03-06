<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductSearchData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProductSearchTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadProductSearchData::class]);
    }

    private function getRepository(): ProductRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(Product::class);
    }

    /**
     * @dataProvider searchQueryBuilderDataProvider
     */
    public function testGetSearchQueryBuilder(string $search, array $expectedSkus): void
    {
        $queryBuilder = $this->getRepository()->getSearchQueryBuilder($search, 0, 100);
        $result = array_map(
            static fn (array $product) => $product['sku'],
            $queryBuilder->getQuery()->getArrayResult()
        );

        sort($result);
        sort($expectedSkus);

        self::assertEquals($expectedSkus, $result);
    }

    public function searchQueryBuilderDataProvider(): array
    {
        return [
            'exact match - Yellow Pine' => [
                'search' => 'Yellow Pine',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'double space should match' => [
                'search' => 'Yellow  Pine',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'triple space should match' => [
                'search' => 'Yellow   Pine',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'single word - Yellow' => [
                'search' => 'Yellow',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'single word - Pine' => [
                'search' => 'Pine',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'reversed words - Pine Yellow' => [
                'search' => 'Pine Yellow',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'partial match - Yello Pine' => [
                'search' => 'Yello Pine',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'partial match single word - Yello' => [
                'search' => 'Yello',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'search by SKU' => [
                'search' => 'YELLOW-PINE',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'search all with common word - partial' => [
                'search' => 'e',
                'expectedSkus' => ['YELLOW-PINE-SKU', 'RED-OAK-SKU', 'BLUE-SPRUCE-SKU'],
            ],
            'no match' => [
                'search' => 'Nonexistent Product',
                'expectedSkus' => [],
            ],
            'case insensitive search' => [
                'search' => 'yellow pine',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'case insensitive search uppercase' => [
                'search' => 'YELLOW PINE',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
            'leading and trailing spaces' => [
                'search' => ' Yellow Pine ',
                'expectedSkus' => ['YELLOW-PINE-SKU'],
            ],
        ];
    }
}
