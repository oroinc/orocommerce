<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;

/**
 * Restrict product urls when using directing canonical Url with enabled localization.
 */
class RestrictSitemapProductSlugByLocaleListener
{
    public function __construct(
        private CanonicalUrlGenerator $canonicalUrlGenerator,
        private AbstractWebsiteLocalizationProvider $websiteLocalizationProvider
    ) {
    }

    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event): void
    {
        /** @var Website $website */
        $website = $event->getWebsite();
        if ($this->canonicalUrlGenerator->isDirectUrlEnabled($website)) {
            $queryBuilder = $event->getQueryBuilder();
            $localizationIds = array_keys($this->websiteLocalizationProvider->getLocalizations($website));
            $expr = $queryBuilder->expr();

            $case = <<<CASE
                (SELECT count(existp.id)
                FROM Oro\Bundle\ProductBundle\Entity\Product existp
                LEFT JOIN existp.slugs exists
                LEFT JOIN exists.localization existl
                WHERE existp = entityAlias AND existl IN (:ids)) < :localeCounts
CASE;
            $queryBuilder->leftJoin('slugs.localization', 'localization')
                ->andWhere($expr->orX(
                    $expr->andX($expr->isNull('localization'), $case),
                    $expr->in('localization', ':ids')
                ))
                ->setParameter('ids', $localizationIds)
                ->setParameter('localeCounts', count($localizationIds))
            ;
        }
    }
}
