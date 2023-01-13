<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Filter;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadFilterProductVisibilityData;

class VisibilityChoiceFilterTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadFilterProductVisibilityData::class]);
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(array $filter, int $total, array $customers)
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $website = $this->getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $scope = $this->getContainer()->get('oro_visibility.provider.visibility_scope_provider')
            ->getProductVisibilityScope($website);

        $gridParams = [
            'customer-product-visibility-grid:website[target_entity_id]' => $product->getId(),
            'customer-product-visibility-grid:website[scope_id]' => $scope->getId(),
        ];
        foreach ($filter as $key => $filterValue) {
            $gridParams["customer-product-visibility-grid:website[_filter][visibility][value][$key]"] = $filterValue;
        }

        $response = $this->client->requestGrid(
            [
                'gridName' => 'customer-product-visibility-grid',
            ],
            $gridParams,
            true
        );
        $result = $this->getJsonResponseContent($response, 200);

        $resultCustomers = array_map(
            function ($row) {
                return $row['name'];
            },
            $result['data']
        );
        self::assertEqualsCanonicalizing($customers, $resultCustomers);
        $this->assertSame($total, $result['options']['totalRecords']);
    }

    public function filterDataProvider(): array
    {
        return [
            'filter by non default visibility' => [
                'filter' => ['visible'],
                'total' => 3,
                'customers' => [
                    'customer.level_1',
                    'customer.level_1.1',
                    'customer.level_1.1.1',
                ],
            ],
            'filter by multiple non default visibility' => [
                'filter' => ['visible', 'hidden'],
                'total' => 5,
                'customers' => [
                    'customer.level_1',
                    'customer.level_1.1',
                    'customer.level_1.1.1',
                    'customer.level_1.2',
                    'customer.level_1.2.1',
                ],
            ],
            'filter by default visibility' => [
                'filter' => ['customer_group'],
                'total' => 11,
                'customers' => [
                    'CustomerUser CustomerUser',
                    'customer.orphan',
                    'customer.level_1.1.2',
                    'customer.level_1.2.1.1',
                    'customer.level_1.3',
                    'customer.level_1.3.1',
                    'customer.level_1.3.1.1',
                    'customer.level_1.4',
                    'customer.level_1.4.1',
                    'customer.level_1.4.1.1',
                    'customer.level_1_1',
                ],
            ],
            'filter by multiple default and non default visibility' => [
                'filter' => ['customer_group', 'visible'],
                'total' => 14,
                'customers' => [
                    'CustomerUser CustomerUser',
                    'customer.orphan',
                    'customer.level_1.1.2',
                    'customer.level_1.2.1.1',
                    'customer.level_1.3',
                    'customer.level_1.3.1',
                    'customer.level_1.3.1.1',
                    'customer.level_1.4',
                    'customer.level_1.4.1',
                    'customer.level_1.4.1.1',
                    'customer.level_1_1',
                    'customer.level_1',
                    'customer.level_1.1',
                    'customer.level_1.1.1',
                ],
            ],
        ];
    }
}
