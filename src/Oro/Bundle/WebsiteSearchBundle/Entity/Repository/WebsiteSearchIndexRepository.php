<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity\Repository;

use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;

class WebsiteSearchIndexRepository extends SearchIndexRepository
{
    /**
     * @param string $currentAlias
     */
    public function removeIndexByAlias($currentAlias)
    {
        $qb = $this->createQueryBuilder('item');
        $qb->delete()
            ->where($qb->expr()->eq('item.alias', ':current_alias'))
            ->getQuery()
            ->setParameter('current_alias', $currentAlias)
            ->execute();
    }

    /**
     * @param string $temporaryAlias
     * @param string $currentAlias
     */
    public function renameIndexAlias($temporaryAlias, $currentAlias)
    {
        $qb = $this->createQueryBuilder('item');
        $qb->update()->set('item.alias', ':current_alias')
            ->where($qb->expr()->eq('item.alias', ':temporary_alias'))
            ->setParameter('current_alias', $currentAlias)
            ->setParameter('temporary_alias', $temporaryAlias)
            ->getQuery()
            ->execute();
    }
}
