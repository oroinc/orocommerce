<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedContentNodeNormalizer;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class ContentNodeTreeCacheTest extends TestCase
{
    use EntityTrait;

    private CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject $cache;

    private ResolvedContentNodeNormalizer|\PHPUnit\Framework\MockObject\MockObject $normalizer;

    private CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject $cacheItem;

    private ContentNodeTreeCache $contentNodeTreeCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->normalizer = $this->createMock(ResolvedContentNodeNormalizer::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);

        $this->contentNodeTreeCache = new ContentNodeTreeCache(
            $this->cache,
            $this->normalizer
        );
    }

    public function testFetchWhenNoCachedData(): void
    {
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('node_2_scope_5')
            ->willReturn($this->cacheItem);

        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        $this->normalizer
            ->expects(self::never())
            ->method(self::anything());

        self::assertFalse($this->contentNodeTreeCache->fetch(2, [5]));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testFetchWhenCachedDataExist(): void
    {
        $cacheData = [
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
                            ['string' => '/test/content', 'localization' => null, 'fallback' => FallbackType::NONE],
                        ],
                    ],
                    'childNodes' => [],
                ],
            ],
        ];
        $expected = new ResolvedContentNode(
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

        $expected->addChildNode($childResolvedNode);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('node_2_scope_5')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($cacheData);

        $this->normalizer
            ->expects(self::once())
            ->method('denormalize')
            ->with($cacheData, ['tree_depth' => 4])
            ->willReturn($expected);

        self::assertEquals($expected, $this->contentNodeTreeCache->fetch(2, [5], 4));
    }

    public function testShouldSaveEmptyCacheIfNodeNotResolved(): void
    {
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('node_2_scope_5')
            ->willReturn($this->cacheItem);

        $this->normalizer
            ->expects(self::never())
            ->method(self::anything());

        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with([]);

        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem)
            ->willReturn(true);

        $this->contentNodeTreeCache->save(2, [5], null);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldSaveCacheIfNodeResolved(): void
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
        $convertedNode = [
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
                            'sub_iterator' => ['c' => ['entity_class' => Localization::class, 'entity_id' => 3]],
                        ],
                        'localizedUrls' => [['string' => '/test/c', 'localization' => null, 'fallback' => null]],
                    ],
                    'childNodes' => [],
                ],
            ],
        ];

        $this->normalizer
            ->expects(self::once())
            ->method('normalize')
            ->with($resolvedNode)
            ->willReturn($convertedNode);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('node_2_scope_5')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($convertedNode);
        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem)
            ->willReturn(true);

        $this->contentNodeTreeCache->save(2, [5], $resolvedNode);
    }
}
