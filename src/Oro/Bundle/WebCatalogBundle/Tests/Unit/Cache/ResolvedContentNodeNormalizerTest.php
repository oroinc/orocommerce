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
use Oro\Component\Testing\Unit\EntityTrait;

class ResolvedContentNodeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private ResolvedContentNodeFactory|\PHPUnit\Framework\MockObject\MockObject $resolvedContentNodeFactory;

    private ResolvedContentNodeNormalizer $normalizer;

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

        $localizedFallbackValueNormalizer
            ->expects(self::any())
            ->method('denormalize')
            ->willReturnCallback(function (array $value, string $entityClass) {
                self::assertEquals(LocalizedFallbackValue::class, $entityClass);

                return (new LocalizedFallbackValue())
                    ->setString($value['string'])
                    ->setLocalization(
                        isset($value['localization']['id']) ? new LocalizationStub($value['localization']['id']) : null
                    );
            });

        $localizedFallbackValueNormalizer
            ->expects(self::any())
            ->method('normalize')
            ->willReturnCallback(function (AbstractLocalizedFallbackValue $value) {
                return [
                    'string' => $value->getString(),
                    'fallback' => $value->getFallback(),
                    'localization' => $value->getLocalization() ? ['id' => $value->getLocalization()->getId()] : null,
                ];
            });
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
                    'sub_iterator' => new ArrayCollection(
                        ['c' => new LocalizationStub(3)]
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
                    'rewriteVariantTitle' => true,
                    'titles' => [],
                    'contentVariant' => [
                        'slugs' => [],
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
                    'rewriteVariantTitle' => true,
                    'titles' => [
                        ['string' => 'Title 1', 'localization' => null, 'fallback' => null],
                        [
                            'string' => 'Title 1 EN',
                            'localization' => ['id' => 5],
                            'fallback' => 'parent_localization',
                        ],
                    ],
                    'contentVariant' => [
                        'id' => 3,
                        'type' => 'test_type',
                        'test' => 1,
                        'slugs' => [['url' => '/test', 'localization' => null, 'fallback' => null]],
                    ],
                    'childNodes' => [
                        [
                            'id' => 2,
                            'identifier' => 'root__second',
                            'priority' => 2,
                            'rewriteVariantTitle' => false,
                            'titles' => [['string' => 'Child Title 1', 'localization' => null, 'fallback' => null]],
                            'contentVariant' => [
                                'id' => 7,
                                'type' => 'test_type',
                                'sub_array' => ['a' => 'b'],
                                'sub_iterator' => [
                                    'c' => [
                                        'class' => LocalizationStub::class,
                                        'id' => 3,
                                    ],
                                ],
                                'slugs' => [
                                    [
                                        'url' => '/test/c',
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
        $this->resolvedContentNodeFactory
            ->expects(self::any())
            ->method('createFromArray')
            ->willReturnCallback(fn (array $data) => $this->createResolvedNode($data));

        self::assertEquals($expected, $this->normalizer->denormalize($cachedData, $context));
    }

    public function denormalizeDataProvider(): array
    {
        return [
            'without child nodes' => [
                'cachedData' => ['id' => 1, 'identifier' => 'root'],
                'context' => [],
                'expected' => $this->createResolvedNode(['id' => 1, 'identifier' => 'root']),
            ],
            'with child nodes' => [
                'cachedData' => [
                    'id' => 1,
                    'identifier' => 'root',
                    'childNodes' => [['id' => 11, 'identifier' => 'root__node11']],
                ],
                'context' => [],
                'expected' => $this->createResolvedNode(['id' => 1, 'identifier' => 'root'])
                    ->addChildNode($this->createResolvedNode(['id' => 11, 'identifier' => 'root__node11'])),
            ],
            'with tree depth' => [
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
                                        [
                                            'id' => 1111,
                                            'identifier' => 'root__node11__node_111__node_1111',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'context' => ['tree_depth' => 2],
                'expected' => $this->createResolvedNode(['id' => 1, 'identifier' => 'root'])
                    ->addChildNode(
                        $this->createResolvedNode(['id' => 11, 'identifier' => 'root__node11'])
                            ->addChildNode(
                                $this->createResolvedNode(['id' => 111, 'identifier' => 'root__node11__node111'])
                            )
                    ),
            ],
        ];
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
}
