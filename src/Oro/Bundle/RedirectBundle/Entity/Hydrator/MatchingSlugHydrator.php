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
        foreach ($rows as $row) {
            $id = [key($this->_rsm->aliasMap) => ''];
            $nonemptyComponents = [];
            $data = $this->gatherRowData($row, $id, $nonemptyComponents);

            if ($data['scalars']['matchedScopeId'] || !$this->hasScopes($data['data']['slug']['id'])) {
                return [$this->_uow->createEntity(Slug::class, $data['data']['slug'], $this->_hints)];
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
