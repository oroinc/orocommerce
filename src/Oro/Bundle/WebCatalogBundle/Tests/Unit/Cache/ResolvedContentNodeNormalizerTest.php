<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedContentNodeNormalizer;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;
use Oro\Component\Testing\Unit\EntityTrait;

class ResolvedContentNodeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private ResolvedContentNodeNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->normalizer = new ResolvedContentNodeNormalizer($this->doctrineHelper);
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(ResolvedContentNode $resolvedNode, array $expected): void
    {
        $this->doctrineHelper
            ->expects(self::any())
            ->method('isManageableEntity')
            ->willReturn(true);

        $this->doctrineHelper
            ->expects(self::any())
            ->method('getEntityClass')
            ->willReturnCallback(static fn ($object) => get_class($object));

        $this->doctrineHelper
            ->expects(self::any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(static fn ($object) => $object->getId());

        self::assertEquals($expected, $this->normalizer->normalize($resolvedNode));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeDataProvider(): array
    {
        $resolvedNode = new ResolvedContentNode(
            1,
            'root',
            1,
            new ArrayCollection(
                [
                    (new LocalizedFallbackValue())->setString('Title 1'),
                    (new LocalizedFallbackValue())->setString('Title 1 EN')
                        ->setFallback(FallbackType::PARENT_LOCALIZATION)
                        ->setLocalization($this->getEntity(Localization::class, ['id' => 5])),
                ]
            ),
            (new ResolvedContentVariant())->setData(['id' => 3, 'type' => 'test_type', 'test' => 1])
                ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test')),
            true
        );

        $childResolveNode = new ResolvedContentNode(
            2,
            'root__second',
            2,
            new ArrayCollection([(new LocalizedFallbackValue())->setString('Child Title 1')]),
            (new ResolvedContentVariant())->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test/c'))
                ->setData([
                    'id' => 7,
                    'type' => 'test_type',
                    'skipped_null' => null,
                    'sub_array' => ['a' => 'b'],
                    'sub_iterator' => new ArrayCollection(
                        ['c' => $this->getEntity(Localization::class, ['id' => 3])]
                    ),
                ]),
            false
        );
        $resolvedNode->addChildNode($childResolveNode);

        return [
            'empty' => [
                'resolvedNode' => new ResolvedContentNode(
                    1,
                    'sample',
                    1,
                    new ArrayCollection(),
                    new ResolvedContentVariant()
                ),
                'expected' => [
                    'id' => 1,
                    'identifier' => 'sample',
                    'priority' => 1,
                    'resolveVariantTitle' => true,
                    'titles' => [],
                    'contentVariant' => [
                        'data' => [],
                        'localizedUrls' => [],
                    ],
                    'childNodes' => [],
                ],
            ],
            'with titles, resolved content variant and child nodes' => [
                'resolvedNode' => $resolvedNode,
                'expected' => [
                    'id' => $resolvedNode->getId(),
                    'identifier' => $resolvedNode->getIdentifier(),
                    'priority' => 1,
                    'resolveVariantTitle' => true,
                    'titles' => [
                        ['string' => 'Title 1', 'localization' => null, 'fallback' => null],
                        [
                            'string' => 'Title 1 EN',
                            'localization' => ['entity_class' => Localization::class, 'entity_id' => 5],
                            'fallback' => 'parent_localization',
                        ],
                    ],
                    'contentVariant' => [
                        'data' => ['id' => 3, 'type' => 'test_type', 'test' => 1],
                        'localizedUrls' => [['string' => '/test', 'localization' => null, 'fallback' => null]],
                    ],
                    'childNodes' => [
                        [
                            'id' => 2,
                            'identifier' => 'root__second',
                            'priority' => 2,
                            'resolveVariantTitle' => false,
                            'titles' => [['string' => 'Child Title 1', 'localization' => null, 'fallback' => null]],
                            'contentVariant' => [
                                'data' => [
                                    'id' => 7,
                                    'type' => 'test_type',
                                    'sub_array' => ['a' => 'b'],
                                    'sub_iterator' => [
                                        'c' => [
                                            'entity_class' => Localization::class,
                                            'entity_id' => 3,
                                        ],
                                    ],
                                ],
                                'localizedUrls' => [
                                    [
                                        'string' => '/test/c',
                                        'localization' => null,
                                        'fallback' => null,
                                    ],
                                ],
                            ],
                            'childNodes' => [],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testDenormalizeWhenNoId(): void
    {
        $this->expectExceptionObject(
            new InvalidArgumentException(
                'Elements "id", "identifier" are required for the denormalization of ResolvedContentNode'
            )
        );

        $this->normalizer->denormalize([], []);
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize(array $cachedData, array $context, ?ResolvedContentNode $expected): void
    {
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityReference')
            ->willReturnCallback(fn ($className, $id) => $this->getEntity($className, ['id' => $id]));

        self::assertEquals($expected, $this->normalizer->denormalize($cachedData, $context));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function denormalizeDataProvider(): array
    {
        $withTitlesContentVariantAndChildNodes = new ResolvedContentNode(
            1,
            'root',
            1,
            new ArrayCollection(
                [
                    (new LocalizedFallbackValue())->setString('Title 1'),
                    (new LocalizedFallbackValue())
                        ->setString('Title 1 EN')
                        ->setFallback(FallbackType::PARENT_LOCALIZATION)
                        ->setLocalization($this->getEntity(Localization::class, ['id' => 5])),
                ]
            ),
            (new ResolvedContentVariant())
                ->setData(['id' => 3, 'type' => 'test_type', 'test' => 1])
                ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test')),
            true
        );
        $childResolvedNode = new ResolvedContentNode(
            2,
            'root__second',
            2,
            new ArrayCollection(
                [
                    (new LocalizedFallbackValue())->setString('Child Title 1'),
                ]
            ),
            (new ResolvedContentVariant())
                ->setData(['id' => 7, 'type' => 'test_type', 'test' => 2])
                ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test/content')),
            false
        );

        $childResolvedNodeWithoutChildren = clone $childResolvedNode;
        $childResolvedNodeWithoutChildren->setChildNodes(new ArrayCollection());

        $innerChildResolvedNode = new ResolvedContentNode(
            3,
            'root__second__third',
            3,
            new ArrayCollection(
                [
                    (new LocalizedFallbackValue())->setString('Inner Child Title 1'),
                ]
            ),
            (new ResolvedContentVariant())
                ->setData(['id' => 8, 'type' => 'test_type', 'test' => 3])
                ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test/content/inner')),
            false
        );
        $childResolvedNode->addChildNode($innerChildResolvedNode);

        $withTitlesContentVariantChildNodesAndDepth = clone $withTitlesContentVariantAndChildNodes;
        $withTitlesContentVariantChildNodesAndDepth->setChildNodes(
            new ArrayCollection([$childResolvedNodeWithoutChildren])
        );

        $withTitlesContentVariantAndChildNodes->addChildNode($childResolvedNode);

        return [
            'without titles, content variant and child nodes' => [
                'cachedData' => [
                    'id' => 1,
                    'identifier' => 'root',
                    'priority' => 1,
                ],
                'context' => [],
                'expected' => new ResolvedContentNode(
                    1,
                    'root',
                    1,
                    new ArrayCollection(),
                    new ResolvedContentVariant(),
                    true
                ),
            ],
            'with titles' => [
                'cachedData' => [
                    'id' => 1,
                    'identifier' => 'root',
                    'priority' => 1,
                    'resolveVariantTitle' => true,
                    'titles' => [
                        ['string' => 'Title 1', 'localization' => null, 'fallback' => FallbackType::NONE],
                        [
                            'string' => 'Title 1 EN',
                            'localization' => ['entity_class' => Localization::class, 'entity_id' => 5],
                            'fallback' => FallbackType::PARENT_LOCALIZATION,
                        ],
                    ],
                ],
                'context' => [],
                'expected' => new ResolvedContentNode(
                    1,
                    'root',
                    1,
                    new ArrayCollection(
                        [
                            (new LocalizedFallbackValue())->setString('Title 1'),
                            (new LocalizedFallbackValue())
                                ->setString('Title 1 EN')
                                ->setFallback(FallbackType::PARENT_LOCALIZATION)
                                ->setLocalization($this->getEntity(Localization::class, ['id' => 5])),
                        ]
                    ),
                    new ResolvedContentVariant(),
                    true
                ),
            ],
            'with titles, content variant' => [
                'cachedData' => [
                    'id' => 1,
                    'identifier' => 'root',
                    'priority' => 1,
                    'resolveVariantTitle' => true,
                    'titles' => [
                        ['string' => 'Title 1', 'localization' => null, 'fallback' => FallbackType::NONE],
                        [
                            'string' => 'Title 1 EN',
                            'localization' => ['entity_class' => Localization::class, 'entity_id' => 5],
                            'fallback' => FallbackType::PARENT_LOCALIZATION,
                        ],
                    ],
                    'contentVariant' => [
                        'data' => ['id' => 3, 'type' => 'test_type', 'test' => 1],
                        'localizedUrls' => [
                            ['string' => '/test', 'localization' => null, 'fallback' => FallbackType::NONE],
                        ],
                    ],
                ],
                'context' => [],
                'expected' => new ResolvedContentNode(
                    1,
                    'root',
                    1,
                    new ArrayCollection(
                        [
                            (new LocalizedFallbackValue())->setString('Title 1'),
                            (new LocalizedFallbackValue())
                                ->setString('Title 1 EN')
                                ->setFallback(FallbackType::PARENT_LOCALIZATION)
                                ->setLocalization($this->getEntity(Localization::class, ['id' => 5])),
                        ]
                    ),
                    (new ResolvedContentVariant())
                        ->setData(['id' => 3, 'type' => 'test_type', 'test' => 1])
                        ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test')),
                    true
                ),
            ],
            'with titles, content variant, child nodes' => [
                'cachedData' => [
                    'id' => 1,
                    'identifier' => 'root',
                    'priority' => 1,
                    'resolveVariantTitle' => true,
                    'titles' => [
                        ['string' => 'Title 1', 'localization' => null, 'fallback' => FallbackType::NONE],
                        [
                            'string' => 'Title 1 EN',
                            'localization' => ['entity_class' => Localization::class, 'entity_id' => 5],
                            'fallback' => FallbackType::PARENT_LOCALIZATION,
                        ],
                    ],
                    'contentVariant' => [
                        'data' => ['id' => 3, 'type' => 'test_type', 'test' => 1],
                        'localizedUrls' => [
                            ['string' => '/test', 'localization' => null, 'fallback' => FallbackType::NONE],
                        ],
                    ],
                    'childNodes' => [
                        [
                            'id' => 2,
                            'priority' => 2,
                            'identifier' => 'root__second',
                            'resolveVariantTitle' => false,
                            'titles' => [
                                ['string' => 'Child Title 1', 'localization' => null, 'fallback' => FallbackType::NONE],
                            ],
                            'contentVariant' => [
                                'data' => ['id' => 7, 'type' => 'test_type', 'test' => 2],
                                'localizedUrls' => [
                                    [
                                        'string' => '/test/content',
                                        'localization' => null,
                                        'fallback' => FallbackType::NONE,
                                    ],
                                ],
                            ],
                            'childNodes' => [
                                [
                                    'id' => 3,
                                    'priority' => 3,
                                    'identifier' => 'root__second__third',
                                    'resolveVariantTitle' => false,
                                    'titles' => [
                                        [
                                            'string' => 'Inner Child Title 1',
                                            'localization' => null,
                                            'fallback' => FallbackType::NONE,
                                        ],
                                    ],
                                    'contentVariant' => [
                                        'data' => ['id' => 8, 'type' => 'test_type', 'test' => 3],
                                        'localizedUrls' => [
                                            [
                                                'string' => '/test/content/inner',
                                                'localization' => null,
                                                'fallback' => FallbackType::NONE,
                                            ],
                                        ],
                                    ],
                                    'childNodes' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                'context' => [],
                'expected' => $withTitlesContentVariantAndChildNodes,
            ],
            'with titles, content variant, child nodes, tree depth' => [
                'cachedData' => [
                    'id' => 1,
                    'identifier' => 'root',
                    'priority' => 1,
                    'resolveVariantTitle' => true,
                    'titles' => [
                        ['string' => 'Title 1', 'localization' => null, 'fallback' => FallbackType::NONE],
                        [
                            'string' => 'Title 1 EN',
                            'localization' => ['entity_class' => Localization::class, 'entity_id' => 5],
                            'fallback' => FallbackType::PARENT_LOCALIZATION,
                        ],
                    ],
                    'contentVariant' => [
                        'data' => ['id' => 3, 'type' => 'test_type', 'test' => 1],
                        'localizedUrls' => [
                            ['string' => '/test', 'localization' => null, 'fallback' => FallbackType::NONE],
                        ],
                    ],
                    'childNodes' => [
                        [
                            'id' => 2,
                            'priority' => 2,
                            'identifier' => 'root__second',
                            'resolveVariantTitle' => false,
                            'titles' => [
                                ['string' => 'Child Title 1', 'localization' => null, 'fallback' => FallbackType::NONE],
                            ],
                            'contentVariant' => [
                                'data' => ['id' => 7, 'type' => 'test_type', 'test' => 2],
                                'localizedUrls' => [
                                    [
                                        'string' => '/test/content',
                                        'localization' => null,
                                        'fallback' => FallbackType::NONE,
                                    ],
                                ],
                            ],
                            'childNodes' => [
                                [
                                    'id' => 3,
                                    'priority' => 3,
                                    'identifier' => 'root__second__third',
                                    'resolveVariantTitle' => false,
                                    'titles' => [
                                        [
                                            'string' => 'Inner Child Title 1',
                                            'localization' => null,
                                            'fallback' => FallbackType::NONE,
                                        ],
                                    ],
                                    'contentVariant' => [
                                        'data' => ['id' => 8, 'type' => 'test_type', 'test' => 3],
                                        'localizedUrls' => [
                                            [
                                                'string' => '/test/content/inner',
                                                'localization' => null,
                                                'fallback' => FallbackType::NONE,
                                            ],
                                        ],
                                    ],
                                    'childNodes' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                'context' => ['tree_depth' => 1],
                'expected' => $withTitlesContentVariantChildNodesAndDepth,
            ],
        ];
    }
}
