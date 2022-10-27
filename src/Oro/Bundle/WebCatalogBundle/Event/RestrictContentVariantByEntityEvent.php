<?php

namespace Oro\Bundle\WebCatalogBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to restrict content variant query builder by associated entity
 */
class RestrictContentVariantByEntityEvent extends Event
{
    public const NAME = 'oro_web_catalog.restrict_content_variant_by_entity';

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var object
     */
    private $entity;

    /**
     * @var string
     */
    private $variantAlias;

    public function __construct(
        QueryBuilder $queryBuilder,
        object $entity,
        string $variantAlias = 'variant'
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->entity = $entity;
        $this->variantAlias = $variantAlias;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getVariantAlias(): string
    {
        return $this->variantAlias;
    }
}
