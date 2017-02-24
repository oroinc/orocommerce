<?php

namespace Oro\Bundle\SEOBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\Event;

class RestrictSitemapEntitiesEvent extends Event
{
    const NAME = 'oro_seo.event.restrict_sitemap_entity';

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var WebsiteInterface
     */
    protected $website;

    /**
     * @param QueryBuilder $qb
     * @param WebsiteInterface $website
     */
    public function __construct(QueryBuilder $qb, WebsiteInterface $website = null)
    {
        $this->queryBuilder = $qb;
        $this->website = $website;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return WebsiteInterface
     */
    public function getWebsite()
    {
        return $this->website;
    }
}
