<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Layout data provider that helps to build main navigation menu on the front store.
 * Cashes resolved web catalog root items.
 */
class MenuDataProvider
{
    private const IDENTIFIER = 'identifier';
    private const LABEL = 'label';
    private const URL = 'url';
    private const CHILDREN = 'children';

    private ManagerRegistry $doctrine;
    private LocalizationHelper $localizationHelper;
    private RequestWebContentScopeProvider $requestWebContentScopeProvider;
    private WebCatalogProvider $webCatalogProvider;
    private ContentNodeTreeResolverInterface $contentNodeTreeResolver;
    private WebsiteManager $websiteManager;
    private CacheInterface $cache;
    private int $cacheLifeTime;
    private ?ContentNode $rootNode = null;

    public function __construct(
        ManagerRegistry $doctrine,
        WebCatalogProvider $webCatalogProvider,
        ContentNodeTreeResolverInterface $contentNodeTreeResolver,
        LocalizationHelper $localizationHelper,
        RequestWebContentScopeProvider $requestWebContentScopeProvider,
        WebsiteManager $websiteManager
    ) {
        $this->doctrine = $doctrine;
        $this->webCatalogProvider = $webCatalogProvider;
        $this->contentNodeTreeResolver = $contentNodeTreeResolver;
        $this->localizationHelper = $localizationHelper;
        $this->requestWebContentScopeProvider = $requestWebContentScopeProvider;
        $this->websiteManager = $websiteManager;
    }

    public function setCache(CacheInterface $cache, int $lifeTime = 0) : void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    public function getItems(int $maxNodesNestedLevel = null) : array
    {
        $scope = $this->requestWebContentScopeProvider->getScope();
        if (null !== $scope) {
            $cacheKey = $this->getCacheKey($scope, $maxNodesNestedLevel);
            $rootItem = $this->cache->get($cacheKey, function (ItemInterface $item) use ($scope, $maxNodesNestedLevel) {
                if ($this->cacheLifeTime > 0) {
                    $item->expiresAfter($this->cacheLifeTime);
                }
                return $this->getResolvedRootItem($scope, $maxNodesNestedLevel);
            });

            if (array_key_exists(self::CHILDREN, $rootItem)) {
                return $rootItem[self::CHILDREN];
            }
        }

        return [];
    }

    private function getResolvedRootItem(Scope $scope, int $maxNodesNestedLevel = null) : array
    {
        $rootItem = [];
        $rootNode = $this->getRootNode();
        if (!$rootNode) {
            $webCatalog = $this->webCatalogProvider->getWebCatalog();
            if ($webCatalog) {
                $rootNode = $this->getContentNodeRepository()->getRootNodeByWebCatalog($webCatalog);
            }
        }

        if ($rootNode) {
            $resolvedNode = $this->contentNodeTreeResolver->getResolvedContentNode($rootNode, $scope);
            if ($resolvedNode) {
                $rootItem = $this->prepareItemsData($resolvedNode, $maxNodesNestedLevel);
            }
        }

        return $rootItem;
    }

    private function prepareItemsData(ResolvedContentNode $node, int $remainingNestedLevel = null) : array
    {
        $result = [];

        $result[self::IDENTIFIER] = $node->getIdentifier();
        $result[self::LABEL] = (string)$this->localizationHelper->getLocalizedValue($node->getTitles());
        $result[self::URL] = (string)$this->localizationHelper
            ->getLocalizedValue($node->getResolvedContentVariant()->getLocalizedUrls());

        $result[self::CHILDREN] = [];
        if (null === $remainingNestedLevel || $remainingNestedLevel > 0) {
            $childrenRemainingNestedLevel = null !== $remainingNestedLevel ? $remainingNestedLevel - 1 : null;
            foreach ($node->getChildNodes() as $child) {
                $result[self::CHILDREN][] = $this->prepareItemsData(
                    $child,
                    $childrenRemainingNestedLevel
                );
            }
        }

        return $result;
    }

    private function getRootNode() : ?ContentNode
    {
        if ($this->rootNode === null) {
            $website = $this->websiteManager->getCurrentWebsite();
            $this->rootNode = $this->webCatalogProvider->getNavigationRoot($website);
        }

        return $this->rootNode;
    }

    private function getContentNodeRepository() : ContentNodeRepository
    {
        return $this->doctrine->getRepository(ContentNode::class);
    }

    private function getCacheKey(Scope $scope, ?int $maxNodesNestedLevel): string
    {
        $rootNode = $this->getRootNode();
        $localization = $this->localizationHelper->getCurrentLocalization();

        return sprintf(
            'menu_items_%s_%s_%s_%s',
            (string)$maxNodesNestedLevel,
            $scope ? $scope->getId() : 0,
            $rootNode ? $rootNode->getId() : 0,
            $localization ? $localization->getId() : 0
        );
    }
}
