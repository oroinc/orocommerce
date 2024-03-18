<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
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
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\MenuDataProvider;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MenuDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

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

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
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
        $this->cache = $this->createMock(CacheItemPoolInterface::class);

        $this->menuDataProvider = new MenuDataProvider(
            $this->doctrine,
            $this->webCatalogProvider,
            $this->contentNodeTreeResolver,
            $this->localizationHelper,
            $this->requestWebContentScopeProvider,
            $this->websiteManager
        );
        $this->menuDataProvider->setCache($this->cache);
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

        $this->webCatalogProvider->expects($this->once())
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

        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->any())
            ->method('isHit')
            ->willReturn(false);

        $this->cache->expects($this->any())
            ->method('getItem')
            ->willReturn($item);

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
            MenuDataProvider::IDENTIFIER => 'root__node2',
            MenuDataProvider::LABEL => 'node2',
            MenuDataProvider::URL => '/node2',
            MenuDataProvider::CHILDREN => []
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

        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->any())
            ->method('isHit')
            ->willReturn(true);

        $this->cache->expects($this->any())
            ->method('getItem')
            ->with(
                sprintf(
                    'menu_items_%s_1_77_42',
                    null !== $maxNodesNestedLevel ? (string)$maxNodesNestedLevel : ''
                )
            )
            ->willReturn($item);
        $item->expects($this->any())
            ->method('get')
            ->willReturn([MenuDataProvider::CHILDREN => $expectedData]);

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
                        MenuDataProvider::IDENTIFIER => 'root__node2',
                        MenuDataProvider::LABEL => 'node2',
                        MenuDataProvider::URL => '/node2',
                        MenuDataProvider::PRIORITY => 2,
                        MenuDataProvider::CHILDREN => [
                            3 => [
                                MenuDataProvider::IDENTIFIER => 'node3',
                                MenuDataProvider::LABEL => 'node3',
                                MenuDataProvider::URL => '/node3',
                                MenuDataProvider::PRIORITY => 3,
                                MenuDataProvider::CHILDREN => []
                            ],
                            4 => [
                                MenuDataProvider::IDENTIFIER => 'node4',
                                MenuDataProvider::LABEL => 'node4',
                                MenuDataProvider::URL => '/node4',
                                MenuDataProvider::PRIORITY => 4,
                                MenuDataProvider::CHILDREN => []
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
                        MenuDataProvider::IDENTIFIER => 'root__node2',
                        MenuDataProvider::LABEL => 'node2',
                        MenuDataProvider::URL => '/node2',
                        MenuDataProvider::PRIORITY => 2,
                        MenuDataProvider::CHILDREN => []
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
                        MenuDataProvider::IDENTIFIER => 'root__node2',
                        MenuDataProvider::LABEL => 'node2',
                        MenuDataProvider::URL => '/node2',
                        MenuDataProvider::PRIORITY => 2,
                        MenuDataProvider::CHILDREN => [
                            3 => [
                                MenuDataProvider::IDENTIFIER => 'node3',
                                MenuDataProvider::LABEL => 'node3',
                                MenuDataProvider::URL => '/node3',
                                MenuDataProvider::PRIORITY => 3,
                                MenuDataProvider::CHILDREN => []
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

        $nodeTitleCollection = new ArrayCollection([
            (new LocalizedFallbackValue())
                ->setString($title)
        ]);

        $resolvedRootNode = new ResolvedContentNode(
            $id,
            $identifier,
            4,
            $nodeTitleCollection,
            $nodeVariant
        );
        $resolvedRootNode->setPriority($priority);

        foreach ($children as $child) {
            $resolvedRootNode->addChildNode($child);
        }

        return $resolvedRootNode;
    }
}
