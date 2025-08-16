<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueNormalizer;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedContentNodeNormalizer;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentNodeFactory;
use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;

class ResolvedContentNodeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ResolvedContentNodeFactory&MockObject $resolvedContentNodeFactory;
    private ResolvedContentNodeNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->resolvedContentNodeFactory = $this->createMock(ResolvedContentNodeFactory::class);
        $localizedFallbackValueNormalizer = $this->createMock(LocalizedFallbackValueNormalizer::class);

        $this->normalizer = new ResolvedContentNodeNormalizer(
            $this->doctrineHelper,
            $localizedFallbackValueNormalizer,
            $this->resolvedContentNodeFactory
        );

        $localizedFallbackValueNormalizer->expects(self::any())
            ->method('denormalize')
            ->willReturnCallback(function (array $value, string $entityClass) {
                self::assertEquals(LocalizedFallbackValue::class, $entityClass);

                return (new LocalizedFallbackValue())
                    ->setString($value['string'])
                    ->setLocalization(
                        isset($value['localization']['id']) ? new LocalizationStub($value['localization']['id']) : null
                    );
            });

        $localizedFallbackValueNormalizer->expects(self::any())
            ->method('normalize')
            ->willReturnCallback(function (AbstractLocalizedFallbackValue $value) {
                $result = ['s' => $value->getString()];
                if (null !== $value->getFallback()) {
                    $result['f'] = $value->getFallback();
                }
                if (null !== $value->getLocalization()) {
                    $result['l'] = $value->getLocalization()->getId();
                }

                return $result;
            });
    }

    private function createResolvedNode(array $data): ResolvedContentNode
    {
        return new ResolvedContentNode(
            $data['id'],
            $data['identifier'],
            0,
            new ArrayCollection(),
            new ResolvedContentVariant()
        );
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(ResolvedContentNode $resolvedNode, array $expected): void
    {
        $this->doctrineHelper->expects(self::any())
            ->method('isManageableEntity')
            ->willReturn(true);

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityClass')
            ->willReturnCallback(function (object $object) {
                return get_class($object);
            });

        $this->doctrineHelper->expects(self::any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function (object $object) {
                return $object->getId();
            });

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
                        ->setLocalization(new LocalizationStub(5)),
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
                    'sub_iterator' => new ArrayCollection(['c' => new LocalizationStub(3)])
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
                    'i' => 1,
                    'a' => 'sample',
                    'p' => 1,
                    'r' => true,
                    't' => [],
                    'c' => ['s' => []],
                    'n' => []
                ]
            ],
            'with titles, resolved content variant and child nodes' => [
                'resolvedNode' => $resolvedNode,
                'expected' => [
                    'i' => $resolvedNode->getId(),
                    'a' => $resolvedNode->getIdentifier(),
                    'p' => 1,
                    'r' => true,
                    't' => [
                        ['s' => 'Title 1'],
                        ['s' => 'Title 1 EN', 'l' => 5, 'f' => 'parent_localization']
                    ],
                    'c' => [
                        'i' => 3,
                        't' => 'test_type',
                        'test' => 1,
                        's' => [['u' => '/test']]
                    ],
                    'n' => [
                        [
                            'i' => 2,
                            'a' => 'root__second',
                            'p' => 2,
                            'r' => false,
                            't' => [['s' => 'Child Title 1']],
                            'c' => [
                                'i' => 7,
                                't' => 'test_type',
                                'sub_array' => ['a' => 'b'],
                                'sub_iterator' => [
                                    'c' => ['o' => LocalizationStub::class, 'i' => 3]
                                ],
                                's' => [
                                    ['u' => '/test/c']
                                ]
                            ],
                            'n' => []
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testDenormalizeWhenNoId(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Element "id" is required for the denormalization of ResolvedContentNode.'
        ));

        $this->normalizer->denormalize([]);
    }

    public function testDenormalizeWhenNoIdentifier(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Element "identifier" is required for the denormalization of ResolvedContentNode.'
        ));

        $this->normalizer->denormalize(['id' => 1]);
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize(array $cachedData, array $context, ?ResolvedContentNode $expected): void
    {
        $this->resolvedContentNodeFactory->expects(self::any())
            ->method('createFromArray')
            ->willReturnCallback(function (array $data) {
                return $this->createResolvedNode($data);
            });

        self::assertEquals($expected, $this->normalizer->denormalize($cachedData, $context));
    }

    public function denormalizeDataProvider(): array
    {
        return [
            'without child nodes' => [
                'cachedData' => ['i' => 1, 'a' => 'root'],
                'context' => [],
                'expected' => $this->createResolvedNode(['id' => 1, 'identifier' => 'root'])
            ],
            'without child nodes (old format)' => [
                'cachedData' => ['id' => 1, 'identifier' => 'root'],
                'context' => [],
                'expected' => $this->createResolvedNode(['id' => 1, 'identifier' => 'root'])
            ],
            'with child nodes' => [
                'cachedData' => [
                    'i' => 1,
                    'a' => 'root',
                    'n' => [['i' => 11, 'a' => 'root__node11']]
                ],
                'context' => [],
                'expected' => $this->createResolvedNode(['id' => 1, 'identifier' => 'root'])
                    ->addChildNode($this->createResolvedNode(['id' => 11, 'identifier' => 'root__node11'])),
            ],
            'with child nodes (old format)' => [
                'cachedData' => [
                    'id' => 1,
                    'identifier' => 'root',
                    'childNodes' => [['id' => 11, 'identifier' => 'root__node11']]
                ],
                'context' => [],
                'expected' => $this->createResolvedNode(['id' => 1, 'identifier' => 'root'])
                    ->addChildNode($this->createResolvedNode(['id' => 11, 'identifier' => 'root__node11'])),
            ],
            'with tree depth' => [
                'cachedData' => [
                    'i' => 1,
                    'a' => 'root',
                    'n' => [
                        [
                            'i' => 11,
                            'a' => 'root__node11',
                            'n' => [
                                [
                                    'i' => 111,
                                    'a' => 'root__node11__node111',
                                    'n' => [
                                        ['i' => 1111, 'a' => 'root__node11__node_111__node_1111']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'context' => ['tree_depth' => 2],
                'expected' => $this->createResolvedNode(['id' => 1, 'identifier' => 'root'])
                    ->addChildNode(
                        $this->createResolvedNode(['id' => 11, 'identifier' => 'root__node11'])
                            ->addChildNode(
                                $this->createResolvedNode(['id' => 111, 'identifier' => 'root__node11__node111'])
                            )
                    )
            ],
            'with tree depth (old format)' => [
                'cachedData' => [
                    'id' => 1,
                    'identifier' => 'root',
                    'childNodes' => [
                        [
                            'id' => 11,
                            'identifier' => 'root__node11',
                            'childNodes' => [
                                [
                                    'id' => 111,
                                    'identifier' => 'root__node11__node111',
                                    'childNodes' => [
                                        ['id' => 1111, 'identifier' => 'root__node11__node_111__node_1111']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'context' => ['tree_depth' => 2],
                'expected' => $this->createResolvedNode(['id' => 1, 'identifier' => 'root'])
                    ->addChildNode(
                        $this->createResolvedNode(['id' => 11, 'identifier' => 'root__node11'])
                            ->addChildNode(
                                $this->createResolvedNode(['id' => 111, 'identifier' => 'root__node11__node111'])
                            )
                    )
            ]
        ];
    }
}
