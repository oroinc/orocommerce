<?php

namespace Oro\Bundle\PayPalBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;

class PayPalSettingsRepository extends EntityRepository
{
    /**
     * @param string $type
     * @return PayPalSettings[]
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
