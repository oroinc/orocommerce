<?php

namespace Oro\Bundle\InfinitePayBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\InfinitePayBundle\Entity\InfinitePaySettings;

class InfinitePaySettingsRepository extends EntityRepository
{
    /**
     * @param string $type
     *
     * @return InfinitePaySettings[]
     */
    public function getEnabledSettingsByType($type)
    {
        return $this->createQueryBuilder('settings')
            ->innerJoin('settings.channel', 'channel')
            ->andWhere('channel.enabled = true')
            ->andWhere('channel.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }
}
