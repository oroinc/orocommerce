<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Repository\CollectionSortOrderRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionWithSortOrderData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CollectionSortOrderRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([LoadProductCollectionWithSortOrderData::class]);
    }

    private function getRepository(): CollectionSortOrderRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CollectionSortOrder::class);
    }

    /**
     * @dataProvider findBySegmentAndProductIdsDataProvider
     *
     * @param string $segment
     * @param string[] $products
     * @param array $expected
     */
    public function testFindBySegmentAndProductIds(string $segment, array $products, array $expected): void
    {
        $products = array_map(
            fn (string $reference) => $this->hasReference($reference) ? $this->getReference($reference)->getId() : 0,
            $products
        );
        $segment = ($this->hasReference($segment) ? $this->getReference($segment) : null);

        $expected = array_map(fn (string $reference) => $this->getReference($reference), $expected);
        $expected = array_replace(
            [],
            ...
            array_map(static fn ($sortOrder) => [$sortOrder->getProduct()->getId() => $sortOrder], $expected)
        );
        self::assertEquals(
            $expected,
            $this->getRepository()->findBySegmentAndProductIds((int)$segment?->getId(), $products)
        );
    }

    public function findBySegmentAndProductIdsDataProvider(): array
    {
        return [
            'non-existing segment' => [
                'segment' => 'invalid',
                'products' => [LoadProductData::PRODUCT_1],
                'expected' => [],
            ],
            'non-existing product' => [
                'segment' => LoadProductCollectionWithSortOrderData::SEGMENT,
                'products' => ['invalid'],
                'expected' => [],
            ],
            'no products ids' => [
                'segment' => LoadProductCollectionWithSortOrderData::SEGMENT,
                'products' => [],
                'expected' => [],
            ],
            'with single products id' => [
                'segment' => LoadProductCollectionWithSortOrderData::SEGMENT,
                'products' => [LoadProductCollectionWithSortOrderData::PRODUCT_ADDED],
                'expected' => [LoadProductCollectionWithSortOrderData::SORT_ORDER_ADDED],
            ],
            'with multiple products id' => [
                'segment' => LoadProductCollectionWithSortOrderData::SEGMENT,
                'products' => [
                    LoadProductCollectionWithSortOrderData::PRODUCT_ADDED,
                    LoadProductCollectionWithSortOrderData::PRODUCT_REMOVED
                ],
                'expected' => [
                    LoadProductCollectionWithSortOrderData::SORT_ORDER_ADDED,
                    LoadProductCollectionWithSortOrderData::SORT_ORDER_REMOVED
                ],
            ],
        ];
    }

    /**
     * @dataProvider removeBySegmentAndProductIdsDataProvider
     *
     * @param string $segment
     * @param string[] $products
     * @param array $expected
     */
    public function testRemoveBySegmentAndProductIds(string $segment, array $products, array $expected): void
    {
        $products = array_map(
            fn (string $reference) => $this->hasReference($reference) ? $this->getReference($reference)->getId() : 0,
            $products
        );
        $segment = ($this->hasReference($segment) ? $this->getReference($segment) : null);

        $expected = array_map(fn (string $reference) => $this->getReference($reference), $expected);

        $this->getRepository()->removeBySegmentAndProductIds((int)$segment?->getId(), $products);
        $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(CollectionSortOrder::class)
            ->flush();

        self::assertEqualsCanonicalizing($expected, $this->getRepository()->findAll());
    }

    public function removeBySegmentAndProductIdsDataProvider(): array
    {
        return [
            'non-existing segment' => [
                'segment' => 'invalid',
                'products' => [LoadProductData::PRODUCT_1],
                'expected' => [
                    LoadProductCollectionWithSortOrderData::SORT_ORDER_ADDED,
                    LoadProductCollectionWithSortOrderData::SORT_ORDER_REMOVED,
                ],
            ],
            'non-existing product' => [
                'segment' => LoadProductCollectionWithSortOrderData::SEGMENT,
                'products' => ['invalid'],
                'expected' => [
                    LoadProductCollectionWithSortOrderData::SORT_ORDER_ADDED,
                    LoadProductCollectionWithSortOrderData::SORT_ORDER_REMOVED,
                ],
            ],
            'no products ids' => [
                'segment' => LoadProductCollectionWithSortOrderData::SEGMENT,
                'products' => [],
                'expected' => [
                    LoadProductCollectionWithSortOrderData::SORT_ORDER_ADDED,
                    LoadProductCollectionWithSortOrderData::SORT_ORDER_REMOVED,
                ],
            ],
            'with single products id' => [
                'segment' => LoadProductCollectionWithSortOrderData::SEGMENT,
                'products' => [LoadProductCollectionWithSortOrderData::PRODUCT_ADDED],
                'expected' => [LoadProductCollectionWithSortOrderData::SORT_ORDER_REMOVED],
            ],
            'with multiple products id' => [
                'segment' => LoadProductCollectionWithSortOrderData::SEGMENT,
                'products' => [
                    LoadProductCollectionWithSortOrderData::PRODUCT_ADDED,
                    LoadProductCollectionWithSortOrderData::PRODUCT_REMOVED
                ],
                'expected' => [],
            ],
        ];
    }
}
