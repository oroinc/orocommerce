<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;
use Oro\Bundle\VisibilityBundle\Model\CategoryVisibilityQueryBuilderModifier;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Restrict category sitemap generation for each website by category organization and category visibility
 */
class RestrictSitemapCategoryListener
{
    /**
     * @var CategoryVisibilityQueryBuilderModifier
     */
    private $categoryVisibilityQueryBuilderModifier;

    public function __construct(
        CategoryVisibilityQueryBuilderModifier $categoryVisibilityQueryBuilderModifier
    ) {
        $this->categoryVisibilityQueryBuilderModifier = $categoryVisibilityQueryBuilderModifier;
    }

    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        $qb = $event->getQueryBuilder();

        /** @var Website $website */
        $website = $event->getWebsite();
        $qb->andWhere($qb->expr()->eq(sprintf('%s.organization', UrlItemsProvider::ENTITY_ALIAS), ':organization'));
        $qb->setParameter('organization', $website->getOrganization());

        $this->categoryVisibilityQueryBuilderModifier->restrictForAnonymous($qb);
    }
}
