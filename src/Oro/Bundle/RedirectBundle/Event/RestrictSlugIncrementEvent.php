<?php

namespace Oro\Bundle\RedirectBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is used to restrict the list of slugs which should be unique per entity (by adding suffix on duplicates).
 * To restrict slugs, add required conditions to the ORM query builder. Later it is used to retrieve existing slugs.
 */
class RestrictSlugIncrementEvent extends Event
{
    public const NAME = 'oro_redirect.event.restrict_slug_increment';

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var SluggableInterface */
    private $entity;

    public function __construct(QueryBuilder $queryBuilder, SluggableInterface $entity)
    {
        $this->queryBuilder = $queryBuilder;
        $this->entity = $entity;
    }

    public function getEntity(): SluggableInterface
    {
        return $this->entity;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
}
