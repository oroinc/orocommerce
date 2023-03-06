<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\ContentNodeUtils;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeTreeResolverTest extends WebTestCase
{
    private ContentNodeTreeResolverInterface $resolver;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            '@OroWebCatalogBundle/Tests/Functional/ContentNodeUtils/DataFixtures/content_node_tree.yml',
        ]);

        /** @var ContentNodeTreeResolverInterface $resolver */
        $this->resolver = self::getContainer()->get('oro_web_catalog.content_node_tree_resolver');
    }

    private function getResolvedContentNodeWithoutCache(
        ContentNode $node,
        array $scopes,
        array $context = []
    ): ?ResolvedContentNode {
        /** @var ContentNodeTreeCache $mergedCache */
        $mergedCache = self::getContainer()->get('oro_web_catalog.content_node_tree_cache.merged');
        $mergedCache->clear();

        /** @var ContentNodeTreeCache $rootCache */
        $rootCache = self::getContainer()->get('oro_web_catalog.content_node_tree_cache.root');
        $rootCache->delete($node->getId(), array_map(static fn ($scope) => $scope->getId(), $scopes));

        return $this->resolver->getResolvedContentNode($node, $scopes, $context);
    }

    private function getResolvedContentNodeWithoutMergedCache(
        ContentNode $node,
        array $scopes,
        array $context = []
    ): ?ResolvedContentNode {
        /** @var ContentNodeTreeCache $mergedCache */
        $mergedCache = self::getContainer()->get('oro_web_catalog.content_node_tree_cache.merged');
        $mergedCache->clear();

        return $this->resolver->getResolvedContentNode($node, $scopes, $context);
    }

    private function getResolvedContentNodeWithCache(
        ContentNode $node,
        array $scopes,
        array $context = []
    ): ?ResolvedContentNode {
        return $this->resolver->getResolvedContentNode($node, $scopes, $context);
    }

    private function convertResolvedContentNodeToArray(ResolvedContentNode $node): array
    {
        $result = [
            'id' => $node->getId(),
            'identifier' => $node->getIdentifier(),
            'variant' => $this->convertResolvedContentVariantToArray($node->getResolvedContentVariant()),
        ];

        foreach ($node->getChildNodes() as $child) {
            $result['children'][] = $this->convertResolvedContentNodeToArray($child);
        }

        return $result;
    }

    private function convertResolvedContentVariantToArray(ResolvedContentVariant $variant): array
    {
        return [
            'id' => $variant->getId(),
            'type' => $variant->getType(),
        ];
    }

    private function assertResolvedContentNodeEquals(
        array $expected,
        ResolvedContentNode $node,
        string $message = ''
    ): void {
        $this->resolveIds($expected);
        self::assertEquals($expected, $this->convertResolvedContentNodeToArray($node), $message);
    }

    private function resolveIds(array &$item): void
    {
        $item['id'] = $this->getReference($item['id'])->getId();
        $item['variant']['id'] = $this->getReference($item['variant']['id'])->getId();
        if (isset($item['children'])) {
            foreach ($item['children'] as &$child) {
                $this->resolveIds($child);
            }
        }
    }

    public function testForRootNode(): void
    {
        $node = $this->getReference('catalog1_rootNode');
        $scope = $this->getReference('scope_catalog1');

        $expected = [
            'id' => 'catalog1_rootNode',
            'identifier' => 'root__catalog1_rootNode',
            'variant' => [
                'id' => 'catalog1_rootNode_variant',
                'type' => 'product_page',
            ],
            'children' => [
                [
                    'id' => 'catalog1_node1',
                    'identifier' => 'root__catalog1_node1',
                    'variant' => [
                        'id' => 'catalog1_node1_variant',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node11',
                            'identifier' => 'root__catalog1_node11',
                            'variant' => [
                                'id' => 'catalog1_node11_variant',
                                'type' => 'category_page',
                            ],
                            'children' => [
                                [
                                    'id' => 'catalog1_node111',
                                    'identifier' => 'root__catalog1_node111',
                                    'variant' => [
                                        'id' => 'catalog1_node111_variant',
                                        'type' => 'system_page',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'catalog1_node12',
                            'identifier' => 'root__catalog1_node12',
                            'variant' => [
                                'id' => 'catalog1_node12_variant',
                                'type' => 'category_page',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'catalog1_node2',
                    'identifier' => 'root__catalog1_node2',
                    'variant' => [
                        'id' => 'catalog1_node2_variant',
                        'type' => 'category_page',
                    ],
                ],
            ],
        ];

        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutCache($node, [$scope]),
            'Failed asserting that resolved content node equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutMergedCache($node, [$scope]),
            'Failed asserting that the resolved content node without merged cache equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithCache($node, [$scope]),
            'Failed asserting that the resolved content node from cache equals to expected'
        );
    }

    public function testForNotRootNode(): void
    {
        $node = $this->getReference('catalog1_node11');
        $scope = $this->getReference('scope_catalog1');

        $expected = [
            'id' => 'catalog1_node11',
            'identifier' => 'root__catalog1_node11',
            'variant' => [
                'id' => 'catalog1_node11_variant',
                'type' => 'category_page',
            ],
            'children' => [
                [
                    'id' => 'catalog1_node111',
                    'identifier' => 'root__catalog1_node111',
                    'variant' => [
                        'id' => 'catalog1_node111_variant',
                        'type' => 'system_page',
                    ],
                ],
            ],
        ];

        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutCache($node, [$scope]),
            'Failed asserting that resolved content node equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutMergedCache($node, [$scope]),
            'Failed asserting that the resolved content node without merged cache equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithCache($node, [$scope]),
            'Failed asserting that the resolved content node from cache equals to expected'
        );
    }

    public function testForScopeWithLocalization(): void
    {
        $node = $this->getReference('catalog1_node1');
        $scope = $this->getReference('scope_catalog1_es');

        $expected = [
            'id' => 'catalog1_node1',
            'identifier' => 'root__catalog1_node1',
            'variant' => [
                'id' => 'catalog1_node1_variant',
                'type' => 'category_page',
            ],
            'children' => [
                [
                    'id' => 'catalog1_node11',
                    'identifier' => 'root__catalog1_node11',
                    'variant' => [
                        'id' => 'catalog1_node11_variant_es',
                        'type' => 'product_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node111',
                            'identifier' => 'root__catalog1_node111',
                            'variant' => [
                                'id' => 'catalog1_node111_variant',
                                'type' => 'system_page',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'catalog1_node12',
                    'identifier' => 'root__catalog1_node12',
                    'variant' => [
                        'id' => 'catalog1_node12_variant',
                        'type' => 'category_page',
                    ],
                ],
                [
                    'id' => 'catalog1_node13_es',
                    'identifier' => 'root__catalog1_node13_es',
                    'variant' => [
                        'id' => 'catalog1_node13_variant_es',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node131',
                            'identifier' => 'root__catalog1_node131',
                            'variant' => [
                                'id' => 'catalog1_node131_variant_es',
                                'type' => 'category_page',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutCache($node, [$scope]),
            'Failed asserting that resolved content node equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutMergedCache($node, [$scope]),
            'Failed asserting that the resolved content node without merged cache equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithCache($node, [$scope]),
            'Failed asserting that the resolved content node from cache equals to expected'
        );
    }

    public function testForScopeWithCustomer(): void
    {
        $node = $this->getReference('catalog1_node1');
        $scope = $this->getReference('scope_catalog1_customer1');

        $expected = [
            'id' => 'catalog1_node1',
            'identifier' => 'root__catalog1_node1',
            'variant' => [
                'id' => 'catalog1_node1_variant',
                'type' => 'category_page',
            ],
            'children' => [
                [
                    'id' => 'catalog1_node11',
                    'identifier' => 'root__catalog1_node11',
                    'variant' => [
                        'id' => 'catalog1_node11_variant_customer1',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node111',
                            'identifier' => 'root__catalog1_node111',
                            'variant' => [
                                'id' => 'catalog1_node111_variant',
                                'type' => 'system_page',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'catalog1_node12',
                    'identifier' => 'root__catalog1_node12',
                    'variant' => [
                        'id' => 'catalog1_node12_variant',
                        'type' => 'category_page',
                    ],
                ],
                [
                    'id' => 'catalog1_node14_customer1',
                    'identifier' => 'root__catalog1_node14_customer1',
                    'variant' => [
                        'id' => 'catalog1_node14_variant_customer1',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node141',
                            'identifier' => 'root__catalog1_node141',
                            'variant' => [
                                'id' => 'catalog1_node141_variant_customer1',
                                'type' => 'category_page',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'catalog1_node15_customer_group1',
                    'identifier' => 'root__catalog1_node15_customer_group1',
                    'variant' => [
                        'id' => 'catalog1_node15_variant_customer_group1',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node151',
                            'identifier' => 'root__catalog1_node151',
                            'variant' => [
                                'id' => 'catalog1_node151_variant_customer_group1',
                                'type' => 'category_page',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutCache($node, [$scope]),
            'Failed asserting that resolved content node equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutMergedCache($node, [$scope]),
            'Failed asserting that the resolved content node without merged cache equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithCache($node, [$scope]),
            'Failed asserting that the resolved content node from cache equals to expected'
        );
    }

    public function testForScopeWithCustomerGroup(): void
    {
        $node = $this->getReference('catalog1_node1');
        $scope = $this->getReference('scope_catalog1_customer_group1');

        $expected = [
            'id' => 'catalog1_node1',
            'identifier' => 'root__catalog1_node1',
            'variant' => [
                'id' => 'catalog1_node1_variant',
                'type' => 'category_page',
            ],
            'children' => [
                [
                    'id' => 'catalog1_node11',
                    'identifier' => 'root__catalog1_node11',
                    'variant' => [
                        'id' => 'catalog1_node11_variant',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node111',
                            'identifier' => 'root__catalog1_node111',
                            'variant' => [
                                'id' => 'catalog1_node111_variant',
                                'type' => 'system_page',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'catalog1_node12',
                    'identifier' => 'root__catalog1_node12',
                    'variant' => [
                        'id' => 'catalog1_node12_variant',
                        'type' => 'category_page',
                    ],
                ],
                [
                    'id' => 'catalog1_node15_customer_group1',
                    'identifier' => 'root__catalog1_node15_customer_group1',
                    'variant' => [
                        'id' => 'catalog1_node15_variant_customer_group1',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node151',
                            'identifier' => 'root__catalog1_node151',
                            'variant' => [
                                'id' => 'catalog1_node151_variant_customer_group1',
                                'type' => 'category_page',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutCache($node, [$scope]),
            'Failed asserting that resolved content node equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutMergedCache($node, [$scope]),
            'Failed asserting that the resolved content node without merged cache equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithCache($node, [$scope]),
            'Failed asserting that the resolved content node from cache equals to expected'
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testForMultipleScopes(): void
    {
        $node = $this->getReference('catalog1_node1');
        $scope3 = $this->getReference('scope_catalog1_customer1');
        $scope2 = $this->getReference('scope_catalog1_es');
        $scope1 = $this->getReference('scope_catalog1');

        $expected = [
            'id' => 'catalog1_node1',
            'identifier' => 'root__catalog1_node1',
            'variant' => [
                'id' => 'catalog1_node1_variant',
                'type' => 'category_page',
            ],
            'children' => [
                [
                    'id' => 'catalog1_node11',
                    'identifier' => 'root__catalog1_node11',
                    'variant' => [
                        'id' => 'catalog1_node11_variant_customer1',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node111',
                            'identifier' => 'root__catalog1_node111',
                            'variant' => [
                                'id' => 'catalog1_node111_variant',
                                'type' => 'system_page',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'catalog1_node12',
                    'identifier' => 'root__catalog1_node12',
                    'variant' => [
                        'id' => 'catalog1_node12_variant',
                        'type' => 'category_page',
                    ],
                ],
                [
                    'id' => 'catalog1_node13_es',
                    'identifier' => 'root__catalog1_node13_es',
                    'variant' => [
                        'id' => 'catalog1_node13_variant_es',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node131',
                            'identifier' => 'root__catalog1_node131',
                            'variant' => [
                                'id' => 'catalog1_node131_variant_es',
                                'type' => 'category_page',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'catalog1_node14_customer1',
                    'identifier' => 'root__catalog1_node14_customer1',
                    'variant' => [
                        'id' => 'catalog1_node14_variant_customer1',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node141',
                            'identifier' => 'root__catalog1_node141',
                            'variant' => [
                                'id' => 'catalog1_node141_variant_customer1',
                                'type' => 'category_page',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'catalog1_node15_customer_group1',
                    'identifier' => 'root__catalog1_node15_customer_group1',
                    'variant' => [
                        'id' => 'catalog1_node15_variant_customer_group1',
                        'type' => 'category_page',
                    ],
                    'children' => [
                        [
                            'id' => 'catalog1_node151',
                            'identifier' => 'root__catalog1_node151',
                            'variant' => [
                                'id' => 'catalog1_node151_variant_customer_group1',
                                'type' => 'category_page',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutCache($node, [$scope3, $scope2, $scope1]),
            'Failed asserting that resolved content node equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutMergedCache($node, [$scope3, $scope2, $scope1]),
            'Failed asserting that the resolved content node without merged cache equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithCache($node, [$scope3, $scope2, $scope1]),
            'Failed asserting that the resolved content node from cache equals to expected'
        );
    }

    public function testForMultipleScopesWithTreeDepth(): void
    {
        $node = $this->getReference('catalog1_node1');
        $scope3 = $this->getReference('scope_catalog1_customer1');
        $scope2 = $this->getReference('scope_catalog1_es');
        $scope1 = $this->getReference('scope_catalog1');

        $expected = [
            'id' => 'catalog1_node1',
            'identifier' => 'root__catalog1_node1',
            'variant' => [
                'id' => 'catalog1_node1_variant',
                'type' => 'category_page',
            ],
            'children' => [
                [
                    'id' => 'catalog1_node11',
                    'identifier' => 'root__catalog1_node11',
                    'variant' => [
                        'id' => 'catalog1_node11_variant_customer1',
                        'type' => 'category_page',
                    ],
                ],
                [
                    'id' => 'catalog1_node12',
                    'identifier' => 'root__catalog1_node12',
                    'variant' => [
                        'id' => 'catalog1_node12_variant',
                        'type' => 'category_page',
                    ],
                ],
                [
                    'id' => 'catalog1_node13_es',
                    'identifier' => 'root__catalog1_node13_es',
                    'variant' => [
                        'id' => 'catalog1_node13_variant_es',
                        'type' => 'category_page',
                    ],
                ],
                [
                    'id' => 'catalog1_node14_customer1',
                    'identifier' => 'root__catalog1_node14_customer1',
                    'variant' => [
                        'id' => 'catalog1_node14_variant_customer1',
                        'type' => 'category_page',
                    ],
                ],
                [
                    'id' => 'catalog1_node15_customer_group1',
                    'identifier' => 'root__catalog1_node15_customer_group1',
                    'variant' => [
                        'id' => 'catalog1_node15_variant_customer_group1',
                        'type' => 'category_page',
                    ],
                ],
            ],
        ];

        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutCache($node, [$scope3, $scope2, $scope1], ['tree_depth' => 1]),
            'Failed asserting that resolved content node equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithoutMergedCache($node, [$scope3, $scope2, $scope1], ['tree_depth' => 1]),
            'Failed asserting that the resolved content node without merged cache equals to expected'
        );
        $this->assertResolvedContentNodeEquals(
            $expected,
            $this->getResolvedContentNodeWithCache($node, [$scope3, $scope2, $scope1], ['tree_depth' => 1]),
            'Failed asserting that the resolved content node from cache equals to expected'
        );
    }
}
