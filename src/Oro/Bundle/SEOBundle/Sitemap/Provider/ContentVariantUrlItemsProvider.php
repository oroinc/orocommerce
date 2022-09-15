<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Modifier\ScopeQueryBuilderModifier;
use Oro\Bundle\SEOBundle\Modifier\ScopeQueryBuilderModifierInterface;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;

/**
 * Responsible for generating specific addresses(UrlItem) depending on the specified website.
 */
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

    private ?ScopeQueryBuilderModifier $scopeQueryBuilderModifier = null;

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

    public function setScopeQueryBuilderModifier(ScopeQueryBuilderModifierInterface $scopeQueryBuilderModifier): void
    {
        $this->scopeQueryBuilderModifier = $scopeQueryBuilderModifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        // If master catalog is enabled - we do not return catalog nodes
        if ($this->isFeaturesEnabled($website)) {
            return;
        }

        $rootNode = $this->webCatalogProvider->getNavigationRootWithCatalogRootFallback($website);
        if (!$rootNode) {
            return;
        }

        $dumpedLocations = [];
        $scopes = $this->getScopes();
        foreach ($scopes as $scope) {
            $resolvedNode = $this->contentNodeTreeResolver->getResolvedContentNode($rootNode, $scope);
            if (!$resolvedNode) {
                continue;
            }

            /** @var UrlItem $item */
            foreach ($this->processResolvedNode($resolvedNode, $website) as $item) {
                if (in_array($item->getLocation(), $dumpedLocations, true)) {
                    continue;
                }
                $dumpedLocations[] = $item->getLocation();

                yield $item;
            }
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
        return $this->registry->getRepository(ContentNode::class);
    }

    /**
     * @return ObjectRepository|SlugRepository
     */
    protected function getSlugRepository()
    {
        return $this->registry->getRepository(Slug::class);
    }

    private function getScopes(): array
    {
        $qb = $this->registry->getManagerForClass(Scope::class)->createQueryBuilder();
        $qb
            ->from(Scope::class, 'scope')
            ->select('scope')
            ->innerJoin(
                Slug::class,
                'slug',
                Join::WITH,
                $qb->expr()->isMemberOf('scope', 'slug.scopes')
            );

        $this->scopeQueryBuilderModifier->applyScopeCriteria($qb, 'scope');

        return $qb->getQuery()->getResult();
    }
}
