<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
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
    private const PRIORITY = 'priority';
    private const IDENTIFIER = 'identifier';
    private const LABEL = 'label';
    private const URL = 'url';
    private const CHILDREN = 'children';

    private LocalizationHelper $localizationHelper;
    private RequestWebContentScopeProvider $requestWebContentScopeProvider;
    private WebCatalogProvider $webCatalogProvider;
    private ContentNodeTreeResolverInterface $contentNodeTreeResolver;
    private WebsiteManager $websiteManager;
    private CacheInterface $cache;
    private int $cacheLifeTime;
    private ?ContentNode $rootNode = null;

    public function __construct(
        WebCatalogProvider $webCatalogProvider,
        ContentNodeTreeResolverInterface $contentNodeTreeResolver,
        LocalizationHelper $localizationHelper,
        RequestWebContentScopeProvider $requestWebContentScopeProvider,
        WebsiteManager $websiteManager
    ) {
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
        $scopes = $this->requestWebContentScopeProvider->getScopes();
        if ($scopes) {
            $cacheKey = $this->getCacheKey($scopes, $maxNodesNestedLevel);
            $rootItem = $this->cache->get(
                $cacheKey,
                function (ItemInterface $item) use ($scopes, $maxNodesNestedLevel) {
                    if ($this->cacheLifeTime > 0) {
                        $item->expiresAfter($this->cacheLifeTime);
                    }
                    return $this->getResolvedItems($scopes, $maxNodesNestedLevel);
                }
            );

            return $rootItem[self::CHILDREN] ?? [];
        }

        return [];
    }

    private function getResolvedItems(array $scopes, int $maxNodesNestedLevel = null): array
    {
        $resolvedItems = [];
        foreach ($scopes as $scope) {
            $resolvedItems[] = $this->getResolvedRootItem($scope, $maxNodesNestedLevel);
        }

        $rootItem = $this->mergeItems(array_filter($resolvedItems));
        if ($rootItem) {
            $rootItem = reset($rootItem);
        }

        return $rootItem;
    }

    private function mergeItems(array $resolvedItems): array
    {
        if (!$resolvedItems) {
            return [];
        }

        return array_reduce($resolvedItems, function ($accum, $item) {
            $identifier = $item[self::PRIORITY];
            if (array_key_exists($identifier, $accum)) {
                $children = array_merge($accum[$identifier][self::CHILDREN], $item[self::CHILDREN]);
                $accum[$identifier][self::CHILDREN] = $children;
            } else {
                $accum[$identifier] = $item;
            }

            $accum[$identifier][self::CHILDREN] = $this->mergeItems($accum[$identifier][self::CHILDREN]);
            ksort($accum);

            return $accum;
        }, []);
    }

    private function getResolvedRootItem(Scope $scope, int $maxNodesNestedLevel = null): array
    {
        $rootItem = [];
        $rootNode = $this->getRootNode();
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
        $result[self::PRIORITY] = $node->getPriority();
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
            $this->rootNode = $this->webCatalogProvider->getNavigationRootWithCatalogRootFallback($website);
        }

        return $this->rootNode;
    }

    private function getCacheKey(array $scopes, ?int $maxNodesNestedLevel): string
    {
        $scopes = array_map(static fn (Scope $scope) => $scope->getId(), $scopes);
        sort($scopes);
        $rootNode = $this->getRootNode();
        $localization = $this->localizationHelper->getCurrentLocalization();
        $scopesKey = implode("_", array_fill(0, count($scopes), "%s"));

        return sprintf(
            'menu_items_%s_%s_%s_%s',
            (string)$maxNodesNestedLevel,
            vsprintf($scopesKey, $scopes) ?: 0,
            $rootNode ? $rootNode->getId() : 0,
            $localization ? $localization->getId() : 0
        );
    }
}
