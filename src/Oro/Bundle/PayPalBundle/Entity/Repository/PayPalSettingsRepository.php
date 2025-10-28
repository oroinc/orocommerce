<?php

namespace Oro\Bundle\PayPalBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Repository for PayPalSettings entity
 */
class PayPalSettingsRepository extends ServiceEntityRepository
{
    private ?AclHelper $aclHelper = null;

    public function setAclHelper(AclHelper $aclHelper): self
    {
        $this->aclHelper = $aclHelper;

        return $this;
    }
    /**
     * @param string $type
     * @return PayPalSettings[]
     */
    public function getEnabledSettingsByType($type)
    {
        $qb = $this->createQueryBuilder('settings')
            ->innerJoin('settings.channel', 'channel')
            ->andWhere('channel.enabled = true')
            ->andWhere('channel.type = :type')
            ->setParameter('type', $type);

        return $this->aclHelper?->apply($qb)->getResult();
    }
}
