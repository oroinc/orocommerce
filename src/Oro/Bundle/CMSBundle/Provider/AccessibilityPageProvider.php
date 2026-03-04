<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration as CMSConfiguration;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides accessibility page URL and title based on system configuration.
 * Supports both ContentNode (when web catalog is active) and CMS Page (when not active).
 */
class AccessibilityPageProvider
{
    public function __construct(
        private ConfigManager $configManager,
        private DoctrineHelper $doctrineHelper,
        private PageRoutingInformationProvider $pageRoutingInformationProvider,
        private UrlGeneratorInterface $router,
        private LocalizationHelper $localizationHelper,
        private ContentNodeTreeResolverInterface $contentNodeTreeResolver,
        private RequestWebContentScopeProvider $requestWebContentScopeProvider
    ) {
    }

    /**
     * Returns the accessibility page URL, or null if not configured.
     */
    public function getAccessibilityPageUrl(): ?string
    {
        if ($this->isWebCatalogActive()) {
            $nodeId = $this->getContentNodeId();
            if ($nodeId) {
                return $this->getContentNodeUrl($nodeId);
            }
        } else {
            $pageId = $this->getCmsPageId();
            if ($pageId) {
                return $this->getCmsPageUrl($pageId);
            }
        }

        return null;
    }

    /**
     * Returns the accessibility page title, or an empty string if not configured.
     */
    public function getAccessibilityPageTitle(): string
    {
        if ($this->isWebCatalogActive()) {
            $nodeId = $this->getContentNodeId();
            if ($nodeId) {
                $contentNode = $this->loadContentNode($nodeId);
                if ($contentNode) {
                    $scopes = $this->requestWebContentScopeProvider->getScopes();
                    if ($scopes) {
                        $resolvedContentNode = $this->contentNodeTreeResolver
                            ->getResolvedContentNode($contentNode, $scopes, ['tree_depth' => 0]);
                        if ($resolvedContentNode) {
                            return (string)$this->localizationHelper->getLocalizedValue(
                                $resolvedContentNode->getTitles()
                            );
                        }
                    }
                }
            }
        } else {
            $pageId = $this->getCmsPageId();
            if ($pageId) {
                $page = $this->loadPage($pageId);
                if ($page) {
                    return (string)$this->localizationHelper->getLocalizedValue($page->getTitles());
                }
            }
        }

        return '';
    }

    private function isWebCatalogActive(): bool
    {
        return (bool)$this->configManager->get(WebCatalogUsageProvider::SETTINGS_KEY);
    }

    private function getContentNodeId(): ?int
    {
        $nodeId = $this->configManager->get(
            Configuration::ROOT_NODE . '.' . Configuration::ACCESSIBILITY_PAGE
        );

        return $nodeId ? (int)$nodeId : null;
    }

    private function getCmsPageId(): ?int
    {
        $pageId = $this->configManager->get(CMSConfiguration::getConfigKeyByName(CMSConfiguration::ACCESSIBILITY_PAGE));

        return $pageId ? (int)$pageId : null;
    }

    private function getContentNodeUrl(int $nodeId): ?string
    {
        $contentNode = $this->loadContentNode($nodeId);
        if (!$contentNode) {
            return null;
        }

        $scopes = $this->requestWebContentScopeProvider->getScopes();
        if (!$scopes) {
            return null;
        }

        $resolvedContentNode = $this->contentNodeTreeResolver
            ->getResolvedContentNode($contentNode, $scopes, ['tree_depth' => 0]);
        if (!$resolvedContentNode) {
            return null;
        }

        $resolvedContentVariant = $resolvedContentNode->getResolvedContentVariant();
        $url = (string)$this->localizationHelper->getLocalizedValue($resolvedContentVariant->getLocalizedUrls());
        if (!$url) {
            return null;
        }

        return $this->router->getContext()->getBaseUrl() . $url;
    }

    private function getCmsPageUrl(int $pageId): ?string
    {
        $page = $this->loadPage($pageId);
        if (!$page) {
            return null;
        }

        $routeData = $this->pageRoutingInformationProvider->getRouteData($page);

        return $this->router->generate(
            $routeData->getRoute(),
            $routeData->getRouteParameters(),
        );
    }

    private function loadContentNode(int $nodeId): ?ContentNode
    {
        /** @var ContentNode|null $contentNode */
        $contentNode = $this->doctrineHelper->getEntityRepository(ContentNode::class)->find($nodeId);

        return $contentNode;
    }

    private function loadPage(int $pageId): ?Page
    {
        /** @var Page|null $page */
        $page = $this->doctrineHelper->getEntityRepository(Page::class)->find($pageId);

        return $page;
    }
}
