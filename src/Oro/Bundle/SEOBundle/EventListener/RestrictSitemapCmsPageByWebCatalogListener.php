<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Sitemap\Provider\CmsPageSitemapRestrictionProvider;
use Oro\Bundle\SEOBundle\Sitemap\Provider\WebCatalogScopeCriteriaProvider;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * Listener for restricting sitemap building for cms pages
 */
class RestrictSitemapCmsPageByWebCatalogListener
{
    private ConfigManager $configManager;
    private WebCatalogScopeCriteriaProvider $scopeCriteriaProvider;
    private CmsPageSitemapRestrictionProvider $provider;

    public function __construct(
        ConfigManager $configManager,
        WebCatalogScopeCriteriaProvider $scopeCriteriaProvider,
        CmsPageSitemapRestrictionProvider $provider
    ) {
        $this->configManager = $configManager;
        $this->scopeCriteriaProvider = $scopeCriteriaProvider;
        $this->provider = $provider;
    }

    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        if ($this->provider->isRestrictionActive($event->getWebsite())) {
            $this->restrict($event);
        }
    }

    private function restrict(RestrictSitemapEntitiesEvent $event)
    {
        $em = $event->getQueryBuilder()->getEntityManager();
        $website = $event->getWebsite();

        $webCatalogId = $this->configManager->get(
            'oro_web_catalog.web_catalog',
            false,
            false,
            $website
        );

        $scopeCriteria = $this->scopeCriteriaProvider->getWebCatalogScopeForAnonymousCustomerGroup($website);

        $qb = $event->getQueryBuilder();
        $rootAliases = $qb->getRootAliases();

        $webCatalogEntitiesQueryBuilder = $this->getWebCatalogEntityIdsQueryBuilder(
            reset($rootAliases),
            $em,
            $scopeCriteria,
            $webCatalogId
        );

        $webCatalogRestriction = $webCatalogEntitiesQueryBuilder->getDQL();
        if ($this->provider->isRestrictedToPagesBelongToWebCatalogOnly($website)) {
            $qb->andWhere($qb->expr()->exists($webCatalogRestriction));
        } else {
            $qb->andWhere($qb->expr()->not($qb->expr()->exists($webCatalogRestriction)));
        }

        foreach ($webCatalogEntitiesQueryBuilder->getParameters() as $parameter) {
            $qb->getParameters()->add($parameter);
        }
    }

    private function getWebCatalogEntityIdsQueryBuilder(
        string $rootAlias,
        EntityManager $em,
        ScopeCriteria $scopeCriteria,
        int $webCatalogId
    ): QueryBuilder {
        $subQb = $em->createQueryBuilder();
        $subQb->select('IDENTITY(contentVariant.cms_page)')
            ->from(ContentVariant::class, 'contentVariant')
            ->innerJoin(
                ContentNode::class,
                'contentNode',
                Join::WITH,
                'contentVariant.node = contentNode'
            )
            ->innerJoin(
                WebCatalog::class,
                'webCatalog',
                Join::WITH,
                'contentNode.webCatalog = webCatalog'
            )
            ->innerJoin('contentVariant.scopes', 'scopes')
            ->where($subQb->expr()->eq($rootAlias, 'IDENTITY(contentVariant.cms_page)'))
            ->andWhere($subQb->expr()->eq('contentVariant.type', ':pageType'))
            ->andWhere($subQb->expr()->eq('webCatalog', ':webCatalogId'))
            ->setParameter('pageType', 'cms_page')
            ->setParameter('webCatalogId', $webCatalogId);

        $scopeCriteria->applyWhereWithPriority($subQb, 'scopes');

        return $subQb;
    }
}
