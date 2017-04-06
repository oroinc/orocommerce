<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;

class ContentVariantUrlItemsProvider implements UrlItemsProviderInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var WebCatalogProvider
     */
    private $webCatalogProvider;

    /**
     * @var ContentNodeTreeResolverInterface
     */
    private $contentNodeTreeResolver;

    /**
     * @var CanonicalUrlGenerator
     */
    private $canonicalUrlGenerator;

    /**
     * @var WebCatalogScopeCriteriaProvider
     */
    private $scopeCriteriaProvider;

    /**
     * @param ManagerRegistry $registry
     * @param WebCatalogProvider $webCatalogProvider
     * @param ContentNodeTreeResolverInterface $contentNodeTreeResolver
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     * @param WebCatalogScopeCriteriaProvider $scopeCriteriaProvider
     */
    public function __construct(
        ManagerRegistry $registry,
        WebCatalogProvider $webCatalogProvider,
        ContentNodeTreeResolverInterface $contentNodeTreeResolver,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        WebCatalogScopeCriteriaProvider $scopeCriteriaProvider
    ) {
        $this->registry = $registry;
        $this->webCatalogProvider = $webCatalogProvider;
        $this->contentNodeTreeResolver = $contentNodeTreeResolver;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->scopeCriteriaProvider = $scopeCriteriaProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        // If master catalog is enabled - do not return we catalog nodes
        if ($this->isFeaturesEnabled($website)) {
            return;
        }

        $webCatalog = $this->webCatalogProvider->getWebCatalog($website);
        if (!$webCatalog) {
            return;
        }
        $rootNode = $this->getContentNodeRepository()->getRootNodeByWebCatalog($webCatalog);
        if (!$rootNode) {
            return;
        }

        $scopeCriteria = $this->scopeCriteriaProvider->getWebCatalogScopeForAnonymousCustomerGroup($website);
        $scope = $this->getScopeRepository()->findMostSuitable($scopeCriteria);

        $resolvedNode = $this->contentNodeTreeResolver->getResolvedContentNode($rootNode, $scope);
        if (!$resolvedNode) {
            return;
        }

        foreach ($this->processResolvedNode($resolvedNode, $website) as $item) {
            yield $item;
        }
    }

    /**
     * @param ResolvedContentNode $node
     * @param WebsiteInterface $website
     * @return \Generator
     */
    protected function processResolvedNode(ResolvedContentNode $node, WebsiteInterface $website)
    {
        foreach ($node->getResolvedContentVariant()->getLocalizedUrls() as $url) {
            $absoluteUrl = $this->canonicalUrlGenerator->getAbsoluteUrl($url, $website);

            yield new UrlItem($absoluteUrl);
        }

        foreach ($node->getChildNodes() as $child) {
            foreach ($this->processResolvedNode($child, $website) as $item) {
                yield $item;
            }
        }
    }

    /**
     * @return ObjectRepository|ContentNodeRepository
     */
    protected function getContentNodeRepository()
    {
        return $this->registry
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);
    }

    /**
     * @return ObjectRepository|ScopeRepository
     */
    protected function getScopeRepository()
    {
        return $this->registry
            ->getManagerForClass(Scope::class)
            ->getRepository(Scope::class);
    }
}
