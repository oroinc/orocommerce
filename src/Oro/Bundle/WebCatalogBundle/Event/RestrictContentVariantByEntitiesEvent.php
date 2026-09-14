<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to restrict a content variant query builder by many entities of one class at once.
 *
 * A listener that recognizes the entity class restricts the query builder by the content variant association
 * that points at the owning entity, selects the identifier of that entity under the owner identifier alias
 * and marks the event as restricted.
 */
class RestrictContentVariantByEntitiesEvent extends Event
{
    public const string NAME = 'oro_web_catalog.restrict_content_variant_by_entities';

    private bool $restricted = false;

    /**
     * @param int[] $entityIds
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly string $entityClass,
        private readonly array $entityIds,
        private readonly string $variantAlias,
        private readonly string $ownerIdAlias
    ) {
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @return int[]
     */
    public function getEntityIds(): array
    {
        return $this->entityIds;
    }

    public function getVariantAlias(): string
    {
        return $this->variantAlias;
    }

    public function getOwnerIdAlias(): string
    {
        return $this->ownerIdAlias;
    }

    public function setRestricted(bool $restricted): void
    {
        $this->restricted = $restricted;
    }

    /**
     * Whether the query builder was restricted by the owning entities.
     * A query builder that was not restricted matches all content variants of the web catalog,
     * so its result cannot be used.
     */
    public function isRestricted(): bool
    {
        return $this->restricted;
    }
}
