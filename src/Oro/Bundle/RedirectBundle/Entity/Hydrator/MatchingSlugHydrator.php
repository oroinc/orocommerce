<?php

namespace Oro\Bundle\RedirectBundle\Entity\Hydrator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Oro\Bundle\RedirectBundle\Entity\Slug;

/**
 * Custom hydrator that increases performance when getting the matching slug.
 */
class MatchingSlugHydrator extends AbstractHydrator
{
    public const NAME = 'oro.redirect.entity.hydrator.matching_slug';

    /**
     * @return Slug[]|array
     */
    protected function hydrateAllData()
    {
        $rows = $this->_stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $key => $row) {
            $id = [key($this->_rsm->aliasMap) => ''];
            $nonemptyComponents = [];
            $rows[$key] = $this->gatherRowData($row, $id, $nonemptyComponents);
        }

        usort($rows, function ($a, $b) {
            if ($a['scalars']['matchedScopeId'] === null && $b['scalars']['matchedScopeId'] === null) {
                return 0;
            }
            if ($a['scalars']['matchedScopeId'] === null) {
                return 1;
            }
            if ($b['scalars']['matchedScopeId'] === null) {
                return -1;
            }

            return 0;
        });

        foreach ($rows as $row) {
            if ($row['scalars']['matchedScopeId'] || !$this->hasScopes($row['data']['slug']['id'])) {
                return [$this->_uow->createEntity(Slug::class, $row['data']['slug'], $this->_hints)];
            }
        }

        return [];
    }

    /**
     * @param int $slugId
     * @return bool
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
