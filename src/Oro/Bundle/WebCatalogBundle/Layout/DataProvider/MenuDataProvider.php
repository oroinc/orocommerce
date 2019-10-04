<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Cache\Layout\DataProviderCacheTrait;

/**
 * Layout data provider that helps to build main navigation menu on the front store.
 * Cashes resolved web catalog root items.
 */
class MenuDataProvider
{
    use DataProviderCacheTrait;

    const IDENTIFIER = 'identifier';
    const LABEL = 'label';
    const URL = 'url';
    const CHILDREN = 'children';

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var RequestWebContentScopeProvider */
    private $requestWebContentScopeProvider;

    /** @var WebCatalogProvider */
    private $webCatalogProvider;

    /** @var ContentNodeTreeResolverInterface */
    private $contentNodeTreeResolver;

    /** @var WebsiteManager */
    private $websiteManager;

    /** @var ContentNode */
    private $rootNode = false;

    /**
     * @param ManagerRegistry $doctrine
     * @param WebCatalogProvider $webCatalogProvider
     * @param ContentNodeTreeResolverInterface $contentNodeTreeResolver
     * @param LocalizationHelper $localizationHelper
     * @param RequestWebContentScopeProvider $requestWebContentScopeProvider
     * @param WebsiteManager $websiteManager
     */
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

    /**
     * @param int|null $maxNodesNestedLevel
     *
     * @return array
     */
    public function getItems(int $maxNodesNestedLevel = null)
    {
        $scope = $this->requestWebContentScopeProvider->getScope();
        if (null !== $scope) {
            $rootItem = $this->getCachedRootItem($scope, $maxNodesNestedLevel);
            if (false === $rootItem) {
                $rootItem = $this->getResolvedRootItem($scope, $maxNodesNestedLevel);
                if ($this->isCacheUsed()) {
                    $this->saveToCache($rootItem);
                }
            }

            if (array_key_exists(self::CHILDREN, $rootItem)) {
                return $rootItem[self::CHILDREN];
            }
        }

        return [];
    }

    /**
     * @param Scope $scope
     * @param int|null $maxNodesNestedLevel
     * @return array
     */
    private function getResolvedRootItem(Scope $scope, int $maxNodesNestedLevel = null)
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

    /**
     * @param Scope $scope
     * @param int|null $maxNodesNestedLevel
     *
     * @return array|bool|false
     */
    private function getCachedRootItem(Scope $scope, int $maxNodesNestedLevel = null)
    {
        if ($this->isCacheUsed()) {
            $rootNode = $this->getRootNode();
            $localization = $this->localizationHelper->getCurrentLocalization();
            $this->initCache([
                'menu_items',
                (string)$maxNodesNestedLevel,
                $scope ? $scope->getId() : 0,
                $rootNode ? $rootNode->getId() : 0,
                $localization ? $localization->getId() : 0
            ]);

            return $this->getFromCache();
        }

        return false;
    }

    /**
     * @param ResolvedContentNode $node
     * @param int|null $remainingNestedLevel
     *
     * @return array
     */
    private function prepareItemsData(ResolvedContentNode $node, int $remainingNestedLevel = null)
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

    /**
     * @return ContentNode|null
     */
    private function getRootNode()
    {
        if ($this->rootNode === false) {
            $website = $this->websiteManager->getCurrentWebsite();
            $this->rootNode = $this->webCatalogProvider->getNavigationRoot($website);
        }

        return $this->rootNode;
    }

    /**
     * @return ContentNodeRepository
     */
    private function getContentNodeRepository()
    {
        return $this->doctrine->getRepository(ContentNode::class);
    }
}
