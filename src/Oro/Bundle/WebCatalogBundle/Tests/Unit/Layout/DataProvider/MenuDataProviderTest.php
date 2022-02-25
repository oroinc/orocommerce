<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolver;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
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

    private const IDENTIFIER = 'identifier';
    private const LABEL = 'label';
    private const URL = 'url';
    private const CHILDREN = 'children';
    private const CACHE_LIFETIME = 600;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var WebCatalogProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $webCatalogProvider;

    /** @var RequestWebContentScopeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $requestWebContentScopeProvider;

    /** @var ContentNodeTreeResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeTreeResolver;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var MenuDataProvider */
    private $menuDataProvider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->webCatalogProvider = $this->createMock(WebCatalogProvider::class);
        $this->requestWebContentScopeProvider = $this->createMock(RequestWebContentScopeProvider::class);
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolver::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->menuDataProvider = new MenuDataProvider(
            $this->doctrine,
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
    public function testGetItems(
        ResolvedContentNode $resolvedRootNode,
        array $expectedData,
        int $maxNodesNestedLevel = null
    ) {
        $webCatalogId = 42;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $webCatalogId]);

        $rootNode = new ContentNode();
        $scope = new Scope();

        $this->requestWebContentScopeProvider->expects($this->once())
            ->method('getScope')
            ->willReturn($scope);

        $this->webCatalogProvider->expects($this->any())
            ->method('getNavigationRoot')
            ->willReturn(null);

        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->willReturn($webCatalog);

        $nodeRepository = $this->createMock(ContentNodeRepository::class);
        $nodeRepository->expects($this->once())
            ->method('getRootNodeByWebCatalog')
            ->with($webCatalog)
            ->willReturn($rootNode);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($nodeRepository);

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
            ->method('getScope')
            ->willReturn($scope);

        $this->webCatalogProvider->expects($this->any())
            ->method('getNavigationRoot')
            ->willReturn($rootNode);

        $this->webCatalogProvider->expects($this->never())
            ->method('getWebCatalog');

        $nodeRepository = $this->createMock(ContentNodeRepository::class);
        $nodeRepository->expects($this->never())
            ->method('getRootNodeByWebCatalog');

        $this->doctrine->expects($this->never())
            ->method('getRepository');

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
            ->method('getScope')
            ->willReturn($scope);

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
            ->method('getNavigationRoot')
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
                'resolvedRootNode' => $this->getResolvedContentNode(1, 'root', 'node1', '/', [
                    $this->getResolvedContentNode(1, 'root__node2', 'node2', '/node2', [
                        $this->getResolvedContentNode(1, 'node3', 'node3', '/node3')
                    ])
                ]),
                'expectedData' => [
                    [
                        self::IDENTIFIER => 'root__node2',
                        self::LABEL => 'node2',
                        self::URL => '/node2',
                        self::CHILDREN => [
                            [
                                self::IDENTIFIER => 'node3',
                                self::LABEL => 'node3',
                                self::URL => '/node3',
                                self::CHILDREN => []
                            ]
                        ]
                    ]
                ]
            ],
            'with maxNodesNestedLevel' => [
                'resolvedRootNode' => $this->getResolvedContentNode(1, 'root', 'node1', '/', [
                    $this->getResolvedContentNode(1, 'root__node2', 'node2', '/node2', [
                        $this->getResolvedContentNode(1, 'node3', 'node3', '/node3')
                    ])
                ]),
                'expectedData' => [
                    [
                        self::IDENTIFIER => 'root__node2',
                        self::LABEL => 'node2',
                        self::URL => '/node2',
                        self::CHILDREN => []
                    ]
                ],
                'maxNodesNestedLevel' => 1
            ],
            'with maxNodesNestedLevel equals to tree nesting level' => [
                'resolvedRootNode' => $this->getResolvedContentNode(1, 'root', 'node1', '/', [
                    $this->getResolvedContentNode(1, 'root__node2', 'node2', '/node2', [
                        $this->getResolvedContentNode(1, 'node3', 'node3', '/node3')
                    ])
                ]),
                'expectedData' => [
                    [
                        self::IDENTIFIER => 'root__node2',
                        self::LABEL => 'node2',
                        self::URL => '/node2',
                        self::CHILDREN => [
                            [
                                self::IDENTIFIER => 'node3',
                                self::LABEL => 'node3',
                                self::URL => '/node3',
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
        array $children = []
    ): ResolvedContentNode {
        $nodeVariant = new ResolvedContentVariant();
        $nodeVariant->addLocalizedUrl((new LocalizedFallbackValue())->setString($url));

        $nodeTitleCollection =  new ArrayCollection([(new LocalizedFallbackValue())
            ->setString($title)]);

        $resolvedRootNode = new ResolvedContentNode(
            $id,
            $identifier,
            $nodeTitleCollection,
            $nodeVariant
        );

        foreach ($children as $child) {
            $resolvedRootNode->addChildNode($child);
        }

        return $resolvedRootNode;
    }
}
