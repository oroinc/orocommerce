<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Modifier\ScopeQueryBuilderModifierInterface;
use Oro\Bundle\SEOBundle\Sitemap\Provider\CmsPageSitemapRestrictionProvider;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * Listener for restricting sitemap building for cms pages
 */
class RestrictSitemapCmsPageByWebCatalogListener
{
    public function __construct(
        private ConfigManager $configManager,
        private CmsPageSitemapRestrictionProvider $provider,
        private ScopeQueryBuilderModifierInterface $scopeQueryBuilderModifier
    ) {
    }

    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event): void
    {
        if ($this->provider->isRestrictionActive($event->getWebsite())) {
            $this->restrict($event);
        }
    }

    private function restrict(RestrictSitemapEntitiesEvent $event): void
    {
        $em = $event->getQueryBuilder()->getEntityManager();
        $website = $event->getWebsite();

        $qb = $event->getQueryBuilder();
        $rootAliases = $qb->getRootAliases();

        $webCatalogId = $this->configManager->get(
            'oro_web_catalog.web_catalog',
            false,
            false,
            $event->getWebsite()
        );
        $webCatalogEntitiesQueryBuilder = $this->getWebCatalogEntityIdsQueryBuilder(
            reset($rootAliases),
            $em,
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

        $this->scopeQueryBuilderModifier->applyScopeCriteria($subQb, 'scopes');

        return $subQb;
    }
}
