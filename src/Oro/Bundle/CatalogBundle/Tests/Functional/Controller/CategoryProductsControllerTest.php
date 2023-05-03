<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Controller;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @dbIsolationPerTest
 */
class CategoryProductsControllerTest extends WebTestCase
{
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->translator = self::getContainer()->get(TranslatorInterface::class);
    }

    public function testUpdateProductsWhenNoCategory(): void
    {
        $this->ajaxRequest(
            'PUT',
            $this->getUrl('oro_catalog_category_products_update', ['id' => PHP_INT_MAX]),
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testUpdateProductsWhenProductsNotFound(): void
    {
        $this->loadFixtures([
            LoadCategoryData::class,
        ]);

        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $this->ajaxRequest(
            'PUT',
            $this->getUrl('oro_catalog_category_products_update', ['id' => $category->getId()]),
            [
                'category_products' => [
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
                            'oro.catalog.category.products.append_products_invalid',
                            [],
                            'validators'
                        ),
                        $this->translator->trans(
                            'oro.catalog.category.products.remove_products_invalid',
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
        $this->loadFixtures([
            LoadCategoryData::class,
        ]);

        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $this->ajaxRequest(
            'PUT',
            $this->getUrl('oro_catalog_category_products_update', ['id' => $category->getId()]),
            [
                'category_products' => [
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
                            'oro.catalog.category.products.sort_order_invalid',
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
        array $fixtures,
        string $categoryReference,
        array $append,
        array $remove,
        array $sortOrder,
        array $expectedIncluded,
        array $expectedSortOrder
    ): void {
        $this->loadFixtures($fixtures);
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        $append = $this->resolveReferences($append);
        $remove = $this->resolveReferences($remove);

        $this->ajaxRequest(
            'PUT',
            $this->getUrl('oro_catalog_category_products_update', ['id' => $category->getId()]),
            [
                'category_products' => [
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

        self::getContainer()->get('doctrine')
            ->getManager()
            ->refresh($category);
        $products = $category->getProducts();

        $actualProductIds = array_map(
            static fn (Product $product) => $product->getId(),
            $products->toArray()
        );
        sort($actualProductIds);

        self::assertEquals(
            $this->resolveReferences($expectedIncluded),
            $actualProductIds,
            'Included products are not as expected'
        );
        self::assertEquals(
            $this->resolveReferences($expectedSortOrder),
            array_replace(
                [],
                ...
                array_map(
                    static fn (Product $product) => [
                        $product->getId() => $product->getCategorySortOrder(),
                    ],
                    $products->toArray()
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
            'empty products collection, append products' => [
                'fixtures' => [
                    LoadCategoryData::class,
                    LoadProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FIRST_LEVEL,
                'append' => ['@product-1', '@product-2'],
                'remove' => [],
                'sortOrder' => [],
                'expectedIncluded' => ['@product-1', '@product-2'],
                'expectedSortOrder' => ['@product-1' => null, '@product-2' => null],
            ],
            'empty product collection, remove products' => [
                'fixtures' => [
                    LoadCategoryData::class,
                    LoadProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FIRST_LEVEL,
                'append' => [],
                'remove' => ['@product-3', '@product-4'],
                'sortOrder' => [],
                'expectedIncluded' => [],
                'expectedSortOrder' => [],
            ],
            'empty product collection, append and remove products' => [
                'fixtures' => [
                    LoadCategoryData::class,
                    LoadProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FIRST_LEVEL,
                'append' => ['@product-1', '@product-2'],
                'remove' => ['@product-2', '@product-3'],
                'sortOrder' => [],
                'expectedIncluded' => ['@product-1'],
                'expectedSortOrder' => ['@product-1' => null],
            ],
            'empty product collection, remove takes precedence when append and remove same products' => [
                'fixtures' => [
                    LoadCategoryData::class,
                    LoadProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FIRST_LEVEL,
                'append' => ['@product-1', '@product-2', '@product-3'],
                'remove' => ['@product-2', '@product-3', '@product-4'],
                'sortOrder' => [],
                'expectedIncluded' => ['@product-1'],
                'expectedSortOrder' => ['@product-1' => null],
            ],
            'empty product collection, append products with sort order' => [
                'fixtures' => [
                    LoadCategoryData::class,
                    LoadProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FIRST_LEVEL,
                'append' => ['@product-1', '@product-2'],
                'remove' => [],
                'sortOrder' => ['@product-1' => ['categorySortOrder' => 42]],
                'expectedIncluded' => ['@product-1', '@product-2'],
                'expectedSortOrder' => ['@product-1' => 42, '@product-2' => null],
            ],
            'empty product collection, sort order of removed product should not be set' => [
                'fixtures' => [
                    LoadCategoryData::class,
                    LoadProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FIRST_LEVEL,
                'append' => [],
                'remove' => ['@product-1', '@product-2'],
                'sortOrder' => ['@product-1' => ['categorySortOrder' => 42]],
                'expectedIncluded' => [],
                'expectedSortOrder' => [],
            ],
            'not empty product collection, append products' => [
                'fixtures' => [
                    LoadCategoryProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FOURTH_LEVEL2,
                'append' => ['@product-3'],
                'remove' => [],
                'sortOrder' => [],
                'expectedIncluded' => ['@product-3', '@продукт-7', '@product-8'],
                'expectedSortOrder' => ['@продукт-7' => null, '@product-8' => null, '@product-3' => null],
            ],
            'not empty product collection, remove products' => [
                'fixtures' => [
                    LoadCategoryProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FOURTH_LEVEL2,
                'append' => [],
                'remove' => ['@продукт-7'],
                'sortOrder' => [],
                'expectedIncluded' => ['@product-8'],
                'expectedSortOrder' => ['@product-8' => null],
            ],
            'not empty product collection, append and remove products' => [
                'fixtures' => [
                    LoadCategoryProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FOURTH_LEVEL2,
                'append' => ['@product-1'],
                'remove' => ['@продукт-7'],
                'sortOrder' => [],
                'expectedIncluded' => ['@product-1', '@product-8'],
                'expectedSortOrder' => ['@product-8' => null, '@product-1' => null],
            ],
            'not empty product collection, remove takes precedence when append and remove same products' => [
                'fixtures' => [
                    LoadCategoryProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FOURTH_LEVEL2,
                'append' => ['@product-1'],
                'remove' => ['@product-1', '@product-8'],
                'sortOrder' => [],
                'expectedIncluded' => ['@продукт-7'],
                'expectedSortOrder' => ['@продукт-7' => null],
            ],
            'not empty product collection, append products with sort order' => [
                'fixtures' => [
                    LoadCategoryProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FOURTH_LEVEL2,
                'append' => ['@product-3'],
                'remove' => [],
                'sortOrder' => ['@product-3' => ['categorySortOrder' => 33]],
                'expectedIncluded' => ['@product-3', '@продукт-7', '@product-8'],
                'expectedSortOrder' => ['@продукт-7' => null, '@product-8' => null, '@product-3' => 33],
            ],
            'not empty product collection, sort order of removed products should not be set' => [
                'fixtures' => [
                    LoadCategoryProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FOURTH_LEVEL2,
                'append' => [],
                'remove' => ['@продукт-7', '@product-8'],
                'sortOrder' => ['@продукт-7' => ['categorySortOrder' => 42]],
                'expectedIncluded' => [],
                'expectedSortOrder' => [],
            ],
            'not empty product collection, set sort order when no appended products' => [
                'fixtures' => [
                    LoadCategoryProductData::class,
                ],
                'categoryReference' => LoadCategoryData::FOURTH_LEVEL2,
                'append' => [],
                'remove' => [],
                'sortOrder' => ['@продукт-7' => ['categorySortOrder' => 111]],
                'expectedIncluded' => ['@продукт-7', '@product-8'],
                'expectedSortOrder' => ['@продукт-7' => 111, '@product-8' => null],
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
