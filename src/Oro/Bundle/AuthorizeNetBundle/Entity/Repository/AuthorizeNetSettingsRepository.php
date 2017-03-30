<?php

namespace Oro\Bundle\AuthorizeNetBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AuthorizeNetBundle\Entity\AuthorizeNetSettings;

class AuthorizeNetSettingsRepository extends EntityRepository
{
    /**
     * @param string $type
     * @return AuthorizeNetSettings[]
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
