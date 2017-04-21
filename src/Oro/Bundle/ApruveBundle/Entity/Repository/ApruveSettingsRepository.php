<?php

namespace Oro\Bundle\ApruveBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ApruveSettingsRepository extends EntityRepository
{
    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param string $type
     *
     * @return ApruveSettings[]
     */
    public function getEnabledSettingsByType($type)
    {
        $qb = $this->createQueryBuilder('settings');
        $qb
            ->innerJoin('settings.channel', 'channel')
            ->andWhere($qb->expr()->eq('channel.enabled', ':channelEnabled'))
            ->andWhere($qb->expr()->eq('channel.type', ':type'))
            ->setParameter('channelEnabled', true)
            ->setParameter('type', $type);

        return $this->aclHelper->apply($qb)->getResult();
    }
}
