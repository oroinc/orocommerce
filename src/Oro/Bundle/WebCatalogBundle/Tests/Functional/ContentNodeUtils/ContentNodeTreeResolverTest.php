<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\ContentNodeUtils;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeTreeResolverTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            '@OroWebCatalogBundle/Tests/Functional/ContentNodeUtils/DataFixtures/content_node_tree.yml'
        ]);
    }

    private function getResolvedContentNode(ContentNode $node, Scope $scope): ?ResolvedContentNode
    {
        /** @var ContentNodeTreeResolverInterface $resolver */
        $resolver = self::getContainer()->get('oro_web_catalog.content_node_tree_resolver');
        /** @var ContentNodeTreeCache $cache */
        $cache = self::getContainer()->get('oro_web_catalog.content_node_tree_cache');

        $cache->delete($node->getId(), $scope->getId());

        return $resolver->getResolvedContentNode($node, $scope);
    }

    private function convertResolvedContentNodeToArray(ResolvedContentNode $node): array
    {
        $result = [
            'id'         => $node->getId(),
            'identifier' => $node->getIdentifier(),
            'variant'    => $this->convertResolvedContentVariantToArray($node->getResolvedContentVariant())
        ];

        foreach ($node->getChildNodes() as $child) {
            $result['children'][] = $this->convertResolvedContentNodeToArray($child);
        }

        return $result;
    }

    private function convertResolvedContentVariantToArray(ResolvedContentVariant $variant): array
    {
        return [
            'id'   => $variant->getId(),
            'type' => $variant->getType()
        ];
    }

    private function assertResolvedContentNodeEquals(array $expected, ResolvedContentNode $node)
    {
        $this->resolveIds($expected);
        self::assertEquals($expected, $this->convertResolvedContentNodeToArray($node));
    }

    private function resolveIds(array &$item)
    {
        $item['id'] = $this->getReference($item['id'])->getId();
        $item['variant']['id'] = $this->getReference($item['variant']['id'])->getId();
        if (isset($item['children'])) {
            foreach ($item['children'] as &$child) {
                $this->resolveIds($child);
            }
        }
    }

    public function testForRootNode()
    {
        $node = $this->getReference('catalog1_rootNode');
        $scope = $this->getReference('scope_catalog1');

        $resolvedNode = $this->getResolvedContentNode($node, $scope);
        $this->assertResolvedContentNodeEquals(
            [
                'id'         => 'catalog1_rootNode',
                'identifier' => 'root__catalog1_rootNode',
                'variant'    => [
                    'id'   => 'catalog1_rootNode_variant',
                    'type' => 'product_page'
                ],
                'children'   => [
                    [
                        'id'         => 'catalog1_node1',
                        'identifier' => 'root__catalog1_node1',
                        'variant'    => [
                            'id'   => 'catalog1_node1_variant',
                            'type' => 'category_page'
                        ],
                        'children'   => [
                            [
                                'id'         => 'catalog1_node11',
                                'identifier' => 'root__catalog1_node11',
                                'variant'    => [
                                    'id'   => 'catalog1_node11_variant',
                                    'type' => 'category_page'
                                ],
                                'children'   => [
                                    [
                                        'id'         => 'catalog1_node111',
                                        'identifier' => 'root__catalog1_node111',
                                        'variant'    => [
                                            'id'   => 'catalog1_node111_variant',
                                            'type' => 'system_page'
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'id'         => 'catalog1_node12',
                                'identifier' => 'root__catalog1_node12',
                                'variant'    => [
                                    'id'   => 'catalog1_node12_variant',
                                    'type' => 'category_page'
                                ]
                            ]
                        ]
                    ],
                    [
                        'id'         => 'catalog1_node2',
                        'identifier' => 'root__catalog1_node2',
                        'variant'    => [
                            'id'   => 'catalog1_node2_variant',
                            'type' => 'category_page'
                        ]
                    ]
                ]
            ],
            $resolvedNode
        );
    }

    public function testForNotRootNode()
    {
        $node = $this->getReference('catalog1_node11');
        $scope = $this->getReference('scope_catalog1');

        $resolvedNode = $this->getResolvedContentNode($node, $scope);
        $this->assertResolvedContentNodeEquals(
            [
                'id'         => 'catalog1_node11',
                'identifier' => 'root__catalog1_node11',
                'variant'    => [
                    'id'   => 'catalog1_node11_variant',
                    'type' => 'category_page'
                ],
                'children'   => [
                    [
                        'id'         => 'catalog1_node111',
                        'identifier' => 'root__catalog1_node111',
                        'variant'    => [
                            'id'   => 'catalog1_node111_variant',
                            'type' => 'system_page'
                        ]
                    ]
                ]
            ],
            $resolvedNode
        );
    }

    public function testForScopeWithLocalization()
    {
        $node = $this->getReference('catalog1_node1');
        $scope = $this->getReference('scope_catalog1_es');

        $resolvedNode = $this->getResolvedContentNode($node, $scope);
        $this->assertResolvedContentNodeEquals(
            [
                'id'         => 'catalog1_node1',
                'identifier' => 'root__catalog1_node1',
                'variant'    => [
                    'id'   => 'catalog1_node1_variant',
                    'type' => 'category_page'
                ],
                'children'   => [
                    [
                        'id'         => 'catalog1_node11',
                        'identifier' => 'root__catalog1_node11',
                        'variant'    => [
                            'id'   => 'catalog1_node11_variant_es',
                            'type' => 'product_page'
                        ],
                        'children'   => [
                            [
                                'id'         => 'catalog1_node111',
                                'identifier' => 'root__catalog1_node111',
                                'variant'    => [
                                    'id'   => 'catalog1_node111_variant',
                                    'type' => 'system_page'
                                ]
                            ]
                        ]
                    ],
                    [
                        'id'         => 'catalog1_node12',
                        'identifier' => 'root__catalog1_node12',
                        'variant'    => [
                            'id'   => 'catalog1_node12_variant',
                            'type' => 'category_page'
                        ]
                    ],
                    [
                        'id'         => 'catalog1_node13_es',
                        'identifier' => 'root__catalog1_node13_es',
                        'variant'    => [
                            'id'   => 'catalog1_node13_variant_es',
                            'type' => 'category_page'
                        ],
                        'children'   => [
                            [
                                'id'         => 'catalog1_node131',
                                'identifier' => 'root__catalog1_node131',
                                'variant'    => [
                                    'id'   => 'catalog1_node131_variant_es',
                                    'type' => 'category_page'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $resolvedNode
        );
    }

    public function testForScopeWithCustomer()
    {
        $node = $this->getReference('catalog1_node1');
        $scope = $this->getReference('scope_catalog1_customer1');

        $resolvedNode = $this->getResolvedContentNode($node, $scope);
        $this->assertResolvedContentNodeEquals(
            [
                'id'         => 'catalog1_node1',
                'identifier' => 'root__catalog1_node1',
                'variant'    => [
                    'id'   => 'catalog1_node1_variant',
                    'type' => 'category_page'
                ],
                'children'   => [
                    [
                        'id'         => 'catalog1_node11',
                        'identifier' => 'root__catalog1_node11',
                        'variant'    => [
                            'id'   => 'catalog1_node11_variant_customer1',
                            'type' => 'category_page'
                        ],
                        'children'   => [
                            [
                                'id'         => 'catalog1_node111',
                                'identifier' => 'root__catalog1_node111',
                                'variant'    => [
                                    'id'   => 'catalog1_node111_variant',
                                    'type' => 'system_page'
                                ]
                            ]
                        ]
                    ],
                    [
                        'id'         => 'catalog1_node12',
                        'identifier' => 'root__catalog1_node12',
                        'variant'    => [
                            'id'   => 'catalog1_node12_variant',
                            'type' => 'category_page'
                        ]
                    ],
                    [
                        'id'         => 'catalog1_node14_customer1',
                        'identifier' => 'root__catalog1_node14_customer1',
                        'variant'    => [
                            'id'   => 'catalog1_node14_variant_customer1',
                            'type' => 'category_page'
                        ],
                        'children'   => [
                            [
                                'id'         => 'catalog1_node141',
                                'identifier' => 'root__catalog1_node141',
                                'variant'    => [
                                    'id'   => 'catalog1_node141_variant_customer1',
                                    'type' => 'category_page'
                                ]
                            ]
                        ]
                    ],
                    [
                        'id'         => 'catalog1_node15_customer_group1',
                        'identifier' => 'root__catalog1_node15_customer_group1',
                        'variant'    => [
                            'id'   => 'catalog1_node15_variant_customer_group1',
                            'type' => 'category_page'
                        ],
                        'children'   => [
                            [
                                'id'         => 'catalog1_node151',
                                'identifier' => 'root__catalog1_node151',
                                'variant'    => [
                                    'id'   => 'catalog1_node151_variant_customer_group1',
                                    'type' => 'category_page'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $resolvedNode
        );
    }

    public function testForScopeWithCustomerGroup()
    {
        $node = $this->getReference('catalog1_node1');
        $scope = $this->getReference('scope_catalog1_customer_group1');

        $resolvedNode = $this->getResolvedContentNode($node, $scope);
        $this->assertResolvedContentNodeEquals(
            [
                'id'         => 'catalog1_node1',
                'identifier' => 'root__catalog1_node1',
                'variant'    => [
                    'id'   => 'catalog1_node1_variant',
                    'type' => 'category_page'
                ],
                'children'   => [
                    [
                        'id'         => 'catalog1_node11',
                        'identifier' => 'root__catalog1_node11',
                        'variant'    => [
                            'id'   => 'catalog1_node11_variant',
                            'type' => 'category_page'
                        ],
                        'children'   => [
                            [
                                'id'         => 'catalog1_node111',
                                'identifier' => 'root__catalog1_node111',
                                'variant'    => [
                                    'id'   => 'catalog1_node111_variant',
                                    'type' => 'system_page'
                                ]
                            ]
                        ]
                    ],
                    [
                        'id'         => 'catalog1_node12',
                        'identifier' => 'root__catalog1_node12',
                        'variant'    => [
                            'id'   => 'catalog1_node12_variant',
                            'type' => 'category_page'
                        ]
                    ],
                    [
                        'id'         => 'catalog1_node15_customer_group1',
                        'identifier' => 'root__catalog1_node15_customer_group1',
                        'variant'    => [
                            'id'   => 'catalog1_node15_variant_customer_group1',
                            'type' => 'category_page'
                        ],
                        'children'   => [
                            [
                                'id'         => 'catalog1_node151',
                                'identifier' => 'root__catalog1_node151',
                                'variant'    => [
                                    'id'   => 'catalog1_node151_variant_customer_group1',
                                    'type' => 'category_page'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $resolvedNode
        );
    }
}
