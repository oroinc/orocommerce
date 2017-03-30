<?php

namespace Oro\Bundle\ApruveBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;

class ApruveSettingsRepository extends EntityRepository
{
    /**
     * @param string $type
     * @return ApruveSettings[]
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
