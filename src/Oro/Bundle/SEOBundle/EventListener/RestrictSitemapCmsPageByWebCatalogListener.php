<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Website\WebsiteInterface;

class RestrictSitemapCmsPageByWebCatalogListener
{
    use FeatureCheckerHolderTrait;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @param ConfigManager $configManager
     * @param ScopeManager $scopeManager
     */
    public function __construct(
        ConfigManager $configManager,
        ScopeManager $scopeManager
    ) {
        $this->configManager = $configManager;
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param RestrictSitemapEntitiesEvent $event
     */
    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        if ($this->isEnabled($event->getWebsite())) {
            $this->restrict($event);
        }
    }

    /**
     * @param RestrictSitemapEntitiesEvent $event
     */
    private function restrict(RestrictSitemapEntitiesEvent $event)
    {
        $em = $event->getQueryBuilder()->getEntityManager();

        $webCatalogId = $this->configManager->get('oro_web_catalog.web_catalog', false, false, $event->getWebsite());
        $anonymousGroupId = $this->configManager
            ->get('oro_customer.anonymous_customer_group', false, false, $event->getWebsite());

        $scopeCriteria = $this->scopeManager->getCriteria(
            'web_content',
            [
                'website' => $event->getWebsite(),
                'webCatalog' => $webCatalogId ? $em->getReference(WebCatalog::class, $webCatalogId) : null,
                'customerGroup' => $anonymousGroupId ? $em->getReference(CustomerGroup::class, $anonymousGroupId) : null
            ]
        );

        $qb = $event->getQueryBuilder();
        $rootAliases = $qb->getRootAliases();

        $webCatalogEntitiesQueryBuilder = $this->getWebCatalogEntityIdsQueryBuilder(
            reset($rootAliases),
            $em,
            $scopeCriteria,
            $webCatalogId
        );

        $qb->andWhere($qb->expr()->exists($webCatalogEntitiesQueryBuilder->getDQL()));

        foreach ($webCatalogEntitiesQueryBuilder->getParameters() as $parameter) {
            $qb->getParameters()->add($parameter);
        }
    }

    /**
     * @param string $rootAlias
     * @param EntityManager $em
     * @param ScopeCriteria $scopeCriteria
     * @param int $webCatalogId
     * @return QueryBuilder
     */
    private function getWebCatalogEntityIdsQueryBuilder(
        $rootAlias,
        EntityManager $em,
        ScopeCriteria $scopeCriteria,
        $webCatalogId
    ) {
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

    /**
     * @param null|WebsiteInterface $website
     * @return bool
     */
    private function isEnabled(WebsiteInterface $website = null)
    {
        return !$this->isFeaturesEnabled($website);
    }
}
