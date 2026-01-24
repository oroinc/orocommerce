<?php

namespace Oro\Bundle\SEOBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched to allow listeners to restrict entities included in the sitemap.
 *
 * This event provides a query builder that listeners can modify to filter which entities should be included
 * in the sitemap. It carries context about the sitemap version and the website being processed, allowing
 * listeners to apply website-specific or version-specific restrictions to the entity query.
 */
class RestrictSitemapEntitiesEvent extends Event
{
    const NAME = 'oro_seo.event.restrict_sitemap_entity';

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var WebsiteInterface|null
     */
    protected $website;

    /**
     * @param QueryBuilder $qb
     * @param int $version
     * @param WebsiteInterface|null $website
     */
    public function __construct(QueryBuilder $qb, $version, ?WebsiteInterface $website = null)
    {
        $this->queryBuilder = $qb;
        $this->version = $version;
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
     * @return WebsiteInterface|null
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }
}
