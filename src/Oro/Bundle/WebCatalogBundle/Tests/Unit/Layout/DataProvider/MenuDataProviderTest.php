<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolver;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\MenuDataProvider;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MenuDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const PRIORITY = 'priority';
    private const IDENTIFIER = 'identifier';
    private const LABEL = 'label';
    private const URL = 'url';
    private const CHILDREN = 'children';
    private const CACHE_LIFETIME = 600;

    /** @var WebCatalogProvider|\PHPUnit\Framework\MockObject\MockObject */
    private WebCatalogProvider $webCatalogProvider;

    /** @var RequestWebContentScopeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private RequestWebContentScopeProvider $requestWebContentScopeProvider;

    /** @var ContentNodeTreeResolver|\PHPUnit\Framework\MockObject\MockObject */
    private ContentNodeTreeResolver $contentNodeTreeResolver;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private LocalizationHelper $localizationHelper;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private WebsiteManager $websiteManager;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private CacheInterface $cache;

    /** @var MenuDataProvider */
    private MenuDataProvider $menuDataProvider;

    protected function setUp(): void
    {
        $this->webCatalogProvider = $this->createMock(WebCatalogProvider::class);
        $this->requestWebContentScopeProvider = $this->createMock(RequestWebContentScopeProvider::class);
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolver::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->menuDataProvider = new MenuDataProvider(
            $this->webCatalogProvider,
            $this->contentNodeTreeResolver,
            $this->localizationHelper,
            $this->requestWebContentScopeProvider,
            $this->websiteManager
        );
        $this->menuDataProvider->setCache($this->cache, self::CACHE_LIFETIME);
    }

    /**
     * @dataProvider getItemsDataProvider
     */
    public function testGetItemsWithNavigationRoot(
        ResolvedContentNode $resolvedRootNode,
        array $expectedData,
        int $maxNodesNestedLevel = null
    ) {
        $rootNode = new ContentNode();
        $scope = new Scope();

        $this->requestWebContentScopeProvider->expects($this->once())
            ->method('getScopes')
            ->willReturn([$scope]);

        $this->webCatalogProvider
            ->expects($this->once())
            ->method('getNavigationRootWithCatalogRootFallback')
            ->willReturn($rootNode);

        $this->contentNodeTreeResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($rootNode, $scope)
            ->willReturn($resolvedRootNode);

        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(function (ArrayCollection $collection) {
                return $collection->first()->getString();
            });

        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $website = $this->getEntity(Website::class, ['id' => 123]);
        $this->websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects(self::once())
                    ->method('expiresAfter')
                    ->with(self::CACHE_LIFETIME)
                    ->willReturn($item);
                return $callback($item);
            });

        $actual = $this->menuDataProvider->getItems($maxNodesNestedLevel);
        $this->assertEquals($expectedData, $actual);
    }

    /**
     * @dataProvider getItemsCachedDataProvider
     */
    public function testGetItemsCached(int $maxNodesNestedLevel = null)
    {
        $scope = $this->getEntity(Scope::class, ['id' => 1]);

        $expectedData = [
            self::IDENTIFIER => 'root__node2',
            self::LABEL => 'node2',
            self::URL => '/node2',
            self::CHILDREN => []
        ];

        $this->requestWebContentScopeProvider->expects($this->once())
            ->method('getScopes')
            ->willReturn([$scope]);

        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $website = $this->getEntity(Website::class, ['id' => 123]);
        $this->websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $rootNode = $this->getEntity(ContentNode::class, ['id' => 77]);
        $this->webCatalogProvider->expects($this->any())
            ->method('getNavigationRootWithCatalogRootFallback')
            ->with($website)
            ->willReturn($rootNode);

        $this->cache->expects($this->once())
            ->method('get')
            ->with(sprintf(
                'menu_items_%s_1_77_42',
                null !== $maxNodesNestedLevel ? (string)$maxNodesNestedLevel : ''
            ))
            ->willReturn([self::CHILDREN => $expectedData]);

        $actual = $this->menuDataProvider->getItems($maxNodesNestedLevel);
        $this->assertEquals($expectedData, $actual);
    }

    public function getItemsCachedDataProvider(): array
    {
        return [
            'without maxNodesNestedLevel' => [],
            'with maxNodesNestedLevel' => [
                'maxNodesNestedLevel' => 2
            ]
        ];
    }

    public function getItemsDataProvider(): array
    {
        return [
            'root without children' => [
                'resolvedRootNode' => $this->getResolvedContentNode(1, 'root', 'node1', '/'),
                'expectedData' => []
            ],
            'root with children' => [
                'resolvedRootNode' => $this->getResolvedContentNode(1, 'root', 'node1', '/', 1, [
                    $this->getResolvedContentNode(2, 'root__node2', 'node2', '/node2', 2, [
                        $this->getResolvedContentNode(4, 'node4', 'node4', '/node4', 4),
                        $this->getResolvedContentNode(3, 'node3', 'node3', '/node3', 3)
                    ])
                ]),
                'expectedData' => [
                    2 => [
                        self::IDENTIFIER => 'root__node2',
                        self::LABEL => 'node2',
                        self::URL => '/node2',
                        self::PRIORITY => 2,
                        self::CHILDREN => [
                            3 => [
                                self::IDENTIFIER => 'node3',
                                self::LABEL => 'node3',
                                self::URL => '/node3',
                                self::PRIORITY => 3,
                                self::CHILDREN => []
                            ],
                            4 => [
                                self::IDENTIFIER => 'node4',
                                self::LABEL => 'node4',
                                self::URL => '/node4',
                                self::PRIORITY => 4,
                                self::CHILDREN => []
                            ]
                        ]
                    ]
                ]
            ],
            'with maxNodesNestedLevel' => [
                'resolvedRootNode' => $this->getResolvedContentNode(1, 'root', 'node1', '/', 1, [
                    $this->getResolvedContentNode(1, 'root__node2', 'node2', '/node2', 2, [
                        $this->getResolvedContentNode(1, 'node3', 'node3', '/node3', 3)
                    ])
                ]),
                'expectedData' => [
                    2 => [
                        self::IDENTIFIER => 'root__node2',
                        self::LABEL => 'node2',
                        self::URL => '/node2',
                        self::PRIORITY => 2,
                        self::CHILDREN => []
                    ]
                ],
                'maxNodesNestedLevel' => 1
            ],
            'with maxNodesNestedLevel equals to tree nesting level' => [
                'resolvedRootNode' => $this->getResolvedContentNode(1, 'root', 'node1', '/', 1, [
                    $this->getResolvedContentNode(1, 'root__node2', 'node2', '/node2', 2, [
                        $this->getResolvedContentNode(1, 'node3', 'node3', '/node3', 3)
                    ])
                ]),
                'expectedData' => [
                    2 => [
                        self::IDENTIFIER => 'root__node2',
                        self::LABEL => 'node2',
                        self::URL => '/node2',
                        self::PRIORITY => 2,
                        self::CHILDREN => [
                            3 => [
                                self::IDENTIFIER => 'node3',
                                self::LABEL => 'node3',
                                self::URL => '/node3',
                                self::PRIORITY => 3,
                                self::CHILDREN => []
                            ]
                        ]
                    ]
                ],
                'maxNodesNestedLevel' => 2
            ]
        ];
    }

    private function getResolvedContentNode(
        string $id,
        string $identifier,
        string $title,
        string $url,
        int $priority = 0,
        array $children = []
    ): ResolvedContentNode {
        $nodeVariant = new ResolvedContentVariant();
        $nodeVariant->addLocalizedUrl((new LocalizedFallbackValue())->setString($url));

        $nodeTitleCollection =  new ArrayCollection([(new LocalizedFallbackValue())
            ->setString($title)]);

        $resolvedRootNode = new ResolvedContentNode(
            $id,
            $identifier,
            $priority,
            $nodeTitleCollection,
            $nodeVariant
        );

        foreach ($children as $child) {
            $resolvedRootNode->addChildNode($child);
        }

        return $resolvedRootNode;
    }
}
