<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Repository\CollectionSortOrderRepository;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductInventoryStatuses;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentTypes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @dbIsolationPerTest
 */
class ProductCollectionContentVariantControllerTest extends WebTestCase
{
    private TranslatorInterface $translator;

    private ProductCollectionDefinitionConverter $definitionConverter;

    private CollectionSortOrderRepository $sortOrderRepository;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadOrganization::class,
            LoadBusinessUnit::class,
            LoadProductInventoryStatuses::class,
            LoadSegmentTypes::class,
            '@OroProductBundle/Tests/Functional/DataFixtures/ProductCollectionContentVariantController/nodes.yml',
        ]);

        $this->translator = self::getContainer()->get(TranslatorInterface::class);
        $this->definitionConverter = self::getContainer()
            ->get('oro_product.service.product_collection_definition_converter');
        $this->sortOrderRepository = self::getContainer()
            ->get('doctrine')
            ->getRepository(CollectionSortOrder::class);
    }

    public function testUpdateProductsWhenNoContentVariant(): void
    {
        $this->ajaxRequest(
            'PUT',
            $this->getUrl('oro_product_collection_content_variant_products_update', ['id' => PHP_INT_MAX]),
            []
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testUpdateProductsWhenContentVariantNotProductCollection(): void
    {
        $contentVariant = $this->getReference('systemPageContentVariant');
        $this->ajaxRequest(
            'PUT',
            $this->getUrl('oro_product_collection_content_variant_products_update', ['id' => $contentVariant->getId()]),
            []
        );

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, 400);

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'success' => false,
                'messages' => [
                    'error' => [
                        $this->translator->trans(
                            'oro.product.product_collection.invalid_content_variant_type',
                            [],
                            'validators'
                        )
                    ]
                ]
            ], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    public function testUpdateProductsWhenProductsNotFound(): void
    {
        $contentVariant = $this->getReference('productCollectionContentVariant1');
        $this->ajaxRequest(
            'PUT',
            $this->getUrl('oro_product_collection_content_variant_products_update', ['id' => $contentVariant->getId()]),
            [
                'product_collection_segment_products' => [
                    'appendProducts' => 99999999,
                    'removeProducts' => 99999999,
                ],
            ]
        );

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, 400);

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'success' => false,
                'messages' => [
                    'error' => [
                        $this->translator->trans(
                            'oro.product.product_collection.append_products_invalid',
                            [],
                            'validators'
                        ),
                        $this->translator->trans(
                            'oro.product.product_collection.remove_products_invalid',
                            [],
                            'validators'
                        )
                    ]
                ]
            ], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    public function testUpdateProductsWhenSortOrderIsInvalid(): void
    {
        $contentVariant = $this->getReference('productCollectionContentVariant1');
        $this->ajaxRequest(
            'PUT',
            $this->getUrl('oro_product_collection_content_variant_products_update', ['id' => $contentVariant->getId()]),
            [
                'product_collection_segment_products' => [
                    'sortOrder' => 'invalid',
                ]
            ]
        );

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, 400);

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'success' => false,
                'messages' => [
                    'error' => [
                        $this->translator->trans(
                            'oro.product.product_collection.sort_order_invalid',
                            [],
                            'validators'
                        ),
                    ]
                ]
            ], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    /**
     * @dataProvider updateProductsDataProvider
     */
    public function testUpdateProducts(
        string $contentVariant,
        array $append,
        array $remove,
        array $sortOrder,
        array $expectedIncluded,
        array $expectedExcluded,
        array $expectedSortOrder
    ): void {
        $contentVariant = $this->getReference($contentVariant);
        $append = $this->resolveReferences($append);
        $remove = $this->resolveReferences($remove);

        $this->ajaxRequest(
            'PUT',
            $this->getUrl('oro_product_collection_content_variant_products_update', ['id' => $contentVariant->getId()]),
            [
                'product_collection_segment_products' => [
                    'appendProducts' => implode(',', $append),
                    'removeProducts' => implode(',', $remove),
                    'sortOrder' => json_encode($this->resolveReferences($sortOrder), JSON_THROW_ON_ERROR),
                ]
            ]
        );

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, 200);

        $this->assertJsonStringEqualsJsonString(
            json_encode(['success' => true], JSON_THROW_ON_ERROR),
            $response->getContent()
        );

        /** @var Segment $segment */
        $segment = $contentVariant->getProductCollectionSegment();
        $definitionParts = $this->definitionConverter->getDefinitionParts($segment->getDefinition());
        self::assertEqualsCanonicalizing(
            $this->resolveReferences($expectedIncluded),
            array_filter(explode(',', $definitionParts[ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY])),
            'Included products are not as expected'
        );
        self::assertEqualsCanonicalizing(
            $this->resolveReferences($expectedExcluded),
            array_filter(explode(',', $definitionParts[ProductCollectionDefinitionConverter::EXCLUDED_FILTER_KEY])),
            'Excluded products are not as expected'
        );

        $sortOrderEntities = $this->sortOrderRepository->findBy(['segment' => $segment->getId()]);
        self::assertEquals(
            $this->resolveReferences($expectedSortOrder),
            array_replace(
                [],
                ...
                array_map(
                    static fn ($sortOrderEntity) => [
                        $sortOrderEntity->getProduct()->getId() => $sortOrderEntity->getSortOrder()
                    ],
                    $sortOrderEntities
                )
            ),
            'Sort order values are not as expected'
        );
    }

    /**
     * @return array[]
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateProductsDataProvider(): array
    {
        return [
            'empty product collection, append products' => [
                'contentVariant' => 'productCollectionContentVariant1',
                'append' => ['@product1', '@product2'],
                'remove' => [],
                'sortOrder' => [],
                'expectedIncluded' => ['@product1', '@product2'],
                'expectedExcluded' => [],
                'expectedSortOrder' => [],
            ],
            'empty product collection, remove products' => [
                'contentVariant' => 'productCollectionContentVariant1',
                'append' => [],
                'remove' => ['@product3', '@product4'],
                'sortOrder' => [],
                'expectedIncluded' => [],
                'expectedExcluded' => ['@product3', '@product4'],
                'expectedSortOrder' => [],
            ],
            'empty product collection, append and remove products' => [
                'contentVariant' => 'productCollectionContentVariant1',
                'append' => ['@product1', '@product2'],
                'remove' => ['@product3', '@product4'],
                'sortOrder' => [],
                'expectedIncluded' => ['@product1', '@product2'],
                'expectedExcluded' => ['@product3', '@product4'],
                'expectedSortOrder' => [],
            ],
            'empty product collection, remove takes precedence when append and remove same products' => [
                'contentVariant' => 'productCollectionContentVariant1',
                'append' => ['@product1', '@product2', '@product3'],
                'remove' => ['@product2', '@product3', '@product4'],
                'sortOrder' => [],
                'expectedIncluded' => ['@product1'],
                'expectedExcluded' => ['@product2', '@product3', '@product4'],
                'expectedSortOrder' => [],
            ],
            'empty product collection, append products with sort order' => [
                'contentVariant' => 'productCollectionContentVariant1',
                'append' => ['@product1', '@product2'],
                'remove' => [],
                'sortOrder' => ['@product1' => ['categorySortOrder' => 42]],
                'expectedIncluded' => ['@product1', '@product2'],
                'expectedExcluded' => [],
                'expectedSortOrder' => ['@product1' => 42],
            ],
            'empty product collection, sort order of removed product should not be set' => [
                'contentVariant' => 'productCollectionContentVariant1',
                'append' => [],
                'remove' => ['@product1', '@product2'],
                'sortOrder' => ['@product1' => ['categorySortOrder' => 42]],
                'expectedIncluded' => [],
                'expectedExcluded' => ['@product1', '@product2'],
                'expectedSortOrder' => [],
            ],
            'empty product collection, set sort order when no added products' => [
                'contentVariant' => 'productCollectionContentVariant1',
                'append' => [],
                'remove' => [],
                'sortOrder' => ['@product1' => ['categorySortOrder' => 42]],
                'expectedIncluded' => [],
                'expectedExcluded' => [],
                'expectedSortOrder' => ['@product1' => 42],
            ],
            'not empty product collection, append products' => [
                'contentVariant' => 'productCollectionContentVariant2',
                'append' => ['@product3'],
                'remove' => [],
                'sortOrder' => [],
                'expectedIncluded' => ['@product1', '@product2', '@product3'],
                'expectedExcluded' => ['@product4'],
                'expectedSortOrder' => ['@product1' => 11, '@product2' => 22],
            ],
            'not empty product collection, remove products' => [
                'contentVariant' => 'productCollectionContentVariant2',
                'append' => [],
                'remove' => ['@product1'],
                'sortOrder' => [],
                'expectedIncluded' => ['@product2'],
                'expectedExcluded' => ['@product3', '@product4', '@product1'],
                'expectedSortOrder' => ['@product2' => 22],
            ],
            'not empty product collection, append and remove products' => [
                'contentVariant' => 'productCollectionContentVariant2',
                'append' => ['@product3'],
                'remove' => ['@product1'],
                'sortOrder' => [],
                'expectedIncluded' => ['@product2', '@product3'],
                'expectedExcluded' => ['@product4', '@product1'],
                'expectedSortOrder' => ['@product2' => 22],
            ],
            'not empty product collection, remove takes precedence when append and remove same products' => [
                'contentVariant' => 'productCollectionContentVariant2',
                'append' => ['@product1', '@product3'],
                'remove' => ['@product1', '@product3'],
                'sortOrder' => [],
                'expectedIncluded' => ['@product2'],
                'expectedExcluded' => ['@product4', '@product1', '@product3'],
                'expectedSortOrder' => ['@product2' => 22],
            ],
            'not empty product collection, append products with sort order' => [
                'contentVariant' => 'productCollectionContentVariant2',
                'append' => ['@product3'],
                'remove' => [],
                'sortOrder' => ['@product3' => ['categorySortOrder' => 33]],
                'expectedIncluded' => ['@product1', '@product2', '@product3'],
                'expectedExcluded' => ['@product4'],
                'expectedSortOrder' => ['@product1' => 11, '@product2' => 22, '@product3' => 33],
            ],
            'not empty product collection, sort order of removed products should not be set' => [
                'contentVariant' => 'productCollectionContentVariant2',
                'append' => [],
                'remove' => ['@product1', '@product2'],
                'sortOrder' => ['@product1' => ['categorySortOrder' => 42]],
                'expectedIncluded' => [],
                'expectedExcluded' => ['@product3', '@product4', '@product1', '@product2'],
                'expectedSortOrder' => [],
            ],
            'not empty product collection, set sort order when no appended products' => [
                'contentVariant' => 'productCollectionContentVariant2',
                'append' => [],
                'remove' => [],
                'sortOrder' => ['@product1' => ['categorySortOrder' => 111]],
                'expectedIncluded' => ['@product1', '@product2'],
                'expectedExcluded' => ['@product3', '@product4'],
                'expectedSortOrder' => ['@product1' => 111, '@product2' => 22],
            ],
            'not empty product collection, sort order of excluded product should not be set' => [
                'contentVariant' => 'productCollectionContentVariant2',
                'append' => [],
                'remove' => [],
                'sortOrder' => ['@product3' => ['categorySortOrder' => 33]],
                'expectedIncluded' => ['@product1', '@product2'],
                'expectedExcluded' => ['@product3', '@product4'],
                'expectedSortOrder' => ['@product1' => 11, '@product2' => 22],
            ],
        ];
    }

    private function resolveReferences(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && str_starts_with($key, '@')) {
                $resolvedKey = $this->getReference(substr($key, 1))->getId();
                $data[$resolvedKey] = $value;
                unset($data[$key]);
                $key = $resolvedKey;
            }

            if (is_array($value)) {
                $data[$key] = $this->resolveReferences($value);
            }

            if (is_string($value) && str_starts_with($value, '@')) {
                $data[$key] = $this->getReference(substr($value, 1))->getId();
            }
        }

        return $data;
    }
}
