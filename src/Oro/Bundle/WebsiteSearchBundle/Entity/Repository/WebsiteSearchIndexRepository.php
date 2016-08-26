<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity\Repository;

use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;

class WebsiteSearchIndexRepository extends SearchIndexRepository
{
    /**
     * @param string $realAlias
     */
    public function removeIndexByAlias($realAlias)
    {
        $qb = $this->createQueryBuilder('item');
        $qb->delete()
            ->where($qb->expr()->eq('item.alias', ':real_alias'))
            ->getQuery()
            ->setParameter('real_alias', $realAlias)
            ->execute();
    }

    /**
     * @param string $tempAlias
     * @param string $realAlias
     */
    public function renameTemporaryIndexAlias($tempAlias, $realAlias)
    {
        $qb = $this->createQueryBuilder('item');
        $qb->update()->set('item.alias', ':real_alias')
            ->where($qb->expr()->eq('item.alias', ':temp_alias'))
            ->setParameter('real_alias', $realAlias)
            ->setParameter('temp_alias', $tempAlias)
            ->getQuery()
            ->execute();
    }
}
