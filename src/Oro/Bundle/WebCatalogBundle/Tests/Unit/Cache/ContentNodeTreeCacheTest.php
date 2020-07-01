<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class ContentNodeTreeCacheTest extends TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ContentNodeTreeCache */
    private $contentNodeTreeCache;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->cache = $this->createMock(Cache::class);

        $this->contentNodeTreeCache = new ContentNodeTreeCache(
            $this->doctrineHelper,
            $this->cache
        );
    }

    public function testFetchWhenNoCachedData()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('node_2_scope_5')
            ->willReturn(false);

        $this->assertFalse($this->contentNodeTreeCache->fetch(2, 5));
    }

    public function testFetchWhenCacheIsEmpty()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('node_2_scope_5')
            ->willReturn([]);

        $this->assertNull($this->contentNodeTreeCache->fetch(2, 5));
    }

    public function testFetchWhenCachedDataExist()
    {
        $cacheData = [
            'id' => 1,
            'identifier' => 'root',
            'resolveVariantTitle' => true,
            'titles' => [
                ['string' => 'Title 1', 'localization' => null, 'fallback' => FallbackType::NONE],
                [
                    'string' => 'Title 1 EN',
                    'localization' => ['entity_class' => Localization::class, 'entity_id' => 5],
                    'fallback' => FallbackType::PARENT_LOCALIZATION
                ]
            ],
            'contentVariant' => [
                'data' => ['id' => 3, 'type' => 'test_type', 'test' => 1],
                'localizedUrls' => [
                    ['string' => '/test', 'localization' => null, 'fallback' => FallbackType::NONE]
                ]
            ],
            'childNodes' => [
                [
                    'id' => 2,
                    'identifier' => 'root__second',
                    'resolveVariantTitle' => false,
                    'titles' => [
                        ['string' => 'Child Title 1', 'localization' => null, 'fallback' => FallbackType::NONE]
                    ],
                    'contentVariant' => [
                        'data' => ['id' => 7, 'type' => 'test_type', 'test' => 2],
                        'localizedUrls' => [
                            ['string' => '/test/content', 'localization' => null, 'fallback' => FallbackType::NONE]
                        ]
                    ],
                    'childNodes' => []
                ]
            ]
        ];
        $expected = new ResolvedContentNode(
            1,
            'root',
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
        $expected->addChildNode(
            new ResolvedContentNode(
                2,
                'root__second',
                new ArrayCollection(
                    [
                        (new LocalizedFallbackValue())->setString('Child Title 1')
                    ]
                ),
                (new ResolvedContentVariant())
                    ->setData(['id' => 7, 'type' => 'test_type', 'test' => 2])
                    ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test/content')),
                false
            )
        );

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('node_2_scope_5')
            ->willReturn($cacheData);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(
                function ($className, $id) {
                    return $this->getEntity($className, ['id' => $id]);
                }
            );

        $this->assertEquals($expected, $this->contentNodeTreeCache->fetch(2, 5));
    }

    public function testShouldSaveEmptyCacheIfNodeNotResolved()
    {
        $this->cache->expects($this->once())
            ->method('save')
            ->with('node_2_scope_5', []);

        $this->contentNodeTreeCache->save(2, 5, null);
    }

    public function testShouldSaveCacheIfNodeResolved()
    {
        $resolvedNode = new ResolvedContentNode(
            1,
            'root',
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
        $resolvedNode->addChildNode(
            new ResolvedContentNode(
                2,
                'root__second',
                new ArrayCollection([(new LocalizedFallbackValue())->setString('Child Title 1')]),
                (new ResolvedContentVariant())->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test/c'))
                    ->setData([
                        'id' => 7,
                        'type' => 'test_type',
                        'skipped_null' => null,
                        'sub_array' => ['a' => 'b'],
                        'sub_iterator' => new ArrayCollection(
                            ['c' => $this->getEntity(Localization::class, ['id' => 3])]
                        )
                    ]),
                false
            )
        );
        $convertedNode = [
            'id' => $resolvedNode->getId(),
            'identifier' => $resolvedNode->getIdentifier(),
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
                'localizedUrls' => [['string' => '/test', 'localization' => null, 'fallback' => null]]
            ],
            'childNodes' => [
                [
                    'id' => 2,
                    'identifier' => 'root__second',
                    'resolveVariantTitle' => false,
                    'titles' => [['string' => 'Child Title 1', 'localization' => null, 'fallback' => null]],
                    'contentVariant' => [
                        'data' => [
                            'id' => 7,
                            'type' => 'test_type',
                            'sub_array' => ['a' => 'b'],
                            'sub_iterator' => ['c' => ['entity_class' => Localization::class, 'entity_id' => 3]]
                        ],
                        'localizedUrls' => [['string' => '/test/c', 'localization' => null, 'fallback' => null]]
                    ],
                    'childNodes' => [],
                ],
            ],
        ];

        $this->doctrineHelper->expects($this->any())->method('isManageableEntity')->willReturn(true);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(
                function ($object) {
                    return get_class($object);
                }
            );
        $this->doctrineHelper->expects($this->any())->method('getSingleEntityIdentifier')
            ->willReturnCallback(
                function ($object) {
                    return $object->getId();
                }
            );

        $this->cache->expects($this->once())
            ->method('save')
            ->with('node_2_scope_5', $convertedNode);

        $this->contentNodeTreeCache->save(2, 5, $resolvedNode);
    }

    public function testDeleteForNode()
    {
        $contentNodeId = 15;
        $node = $this->getEntity(ContentNode::class, ['id' => $contentNodeId]);

        $webCatalog = new WebCatalog();
        $node->setWebCatalog($webCatalog);

        $fooScopeIds = 42;
        $barScopeIds = 2;

        $webCatalogRepository = $this->createMock(WebCatalogRepository::class);
        $webCatalogRepository->expects($this->once())
            ->method('getUsedScopesIds')
            ->with($webCatalog)
            ->willReturn([$fooScopeIds, $barScopeIds]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(WebCatalog::class)
            ->willReturn($webCatalogRepository);

        $fooScopeCacheKey = "node_{$contentNodeId}_scope_{$fooScopeIds}";
        $barScopeCacheKey = "node_{$contentNodeId}_scope_{$barScopeIds}";

        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive([$fooScopeCacheKey], [$barScopeCacheKey]);

        $this->contentNodeTreeCache->deleteForNode($node);
    }
}
