<?php

namespace Oro\Bundle\RedirectBundle\Entity\Hydrator;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Hydrator\AbstractMatchingEntityHydrator;

/**
 * Custom hydrator that increases performance when getting the matching slug.
 * Requires matchedScopeId to be selected
 */
class MatchingSlugHydrator extends AbstractMatchingEntityHydrator
{
    public const NAME = 'oro.redirect.entity.hydrator.matching_slug';

    /**
     * {@inheritDoc}
     */
    protected function getRootEntityAlias(): string
    {
        return 'slug';
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityClass(): string
    {
        return Slug::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function hasScopes($slugId): bool
    {
        $hasScopeQuery = 'SELECT 1 FROM oro_slug_scope WHERE slug_id = :id LIMIT 1';
        $hasScopes = $this->_em->getConnection()
            ->executeQuery($hasScopeQuery, ['id' => $slugId], ['id' => \PDO::PARAM_INT])
            ->fetchColumn();

        return (bool)$hasScopes;
    }
}
