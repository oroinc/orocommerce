<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Cache\Layout\DataProviderCacheTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Layout data provider that helps to build main navigation menu on the front store
 * Cashes resolved webcatalog root items
 */
class MenuDataProvider extends AbstractWebCatalogDataProvider
{
    use DataProviderCacheTrait;

    const IDENTIFIER = 'identifier';
    const LABEL = 'label';
    const URL = 'url';
    const CHILDREN = 'children';

    /**
     * @var WebCatalogProvider
     */
    protected $webCatalogProvider;

    /**
     * @var ContentNodeTreeResolverInterface
     */
    protected $contentNodeTreeResolverFacade;

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @var ContentNode
     */
    private $rootNode = false;

    /**
     * @param ManagerRegistry $registry
     * @param WebCatalogProvider $webCatalogProvider
     * @param ContentNodeTreeResolverInterface $contentNodeTreeResolverFacade
     * @param LocalizationHelper $localizationHelper
     * @param RequestStack $requestStack
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        ManagerRegistry $registry,
        WebCatalogProvider $webCatalogProvider,
        ContentNodeTreeResolverInterface $contentNodeTreeResolverFacade,
        LocalizationHelper $localizationHelper,
        RequestStack $requestStack,
        WebsiteManager $websiteManager
    ) {
        $this->registry = $registry;
        $this->webCatalogProvider = $webCatalogProvider;
        $this->contentNodeTreeResolverFacade = $contentNodeTreeResolverFacade;
        $this->localizationHelper = $localizationHelper;
        $this->requestStack = $requestStack;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(int $maxNodesNestedLevel = null)
    {
        $request = $this->requestStack->getCurrentRequest();

        /** @var Scope $scope */
        if ($request && $scope = $request->attributes->get('_web_content_scope')) {
            $rootItem = $this->getCachedRootItem($scope);

            if ($rootItem === false) {
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
            $resolvedNode = $this->contentNodeTreeResolverFacade->getResolvedContentNode(
                $rootNode,
                $scope,
                $maxNodesNestedLevel
            );

            if ($resolvedNode) {
                $rootItem = $this->prepareItemsData($resolvedNode);
            }
        }

        return $rootItem;
    }

    /**
     * @param Scope $scope
     * @return array|bool|false
     */
    private function getCachedRootItem(Scope $scope)
    {
        if ($this->isCacheUsed()) {
            $rootNode = $this->getRootNode();
            $localization = $this->localizationHelper->getCurrentLocalization();
            $this->initCache([
                'menu_items',
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
     * @return array
     */
    protected function prepareItemsData(ResolvedContentNode $node)
    {
        $result = [];

        $result[self::IDENTIFIER] = $node->getIdentifier();
        $result[self::LABEL] = (string)$this->localizationHelper->getLocalizedValue($node->getTitles());
        $result[self::URL] = (string)$this->localizationHelper
            ->getLocalizedValue($node->getResolvedContentVariant()->getLocalizedUrls());

        $result[self::CHILDREN] = [];
        foreach ($node->getChildNodes() as $child) {
            $result[self::CHILDREN][] = $this->prepareItemsData($child);
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
}
