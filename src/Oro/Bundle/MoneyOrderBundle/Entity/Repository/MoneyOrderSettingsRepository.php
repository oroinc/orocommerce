<?php

namespace Oro\Bundle\MoneyOrderBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Doctrine repository for MoneyOrderSettings entity
 */
class MoneyOrderSettingsRepository extends ServiceEntityRepository
{
    /**
     * @var AclHelper
     */
    private $aclHelper;

    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @return MoneyOrderSettings[]
     */
    public function findWithEnabledChannel()
    {
        $qb = $this->createQueryBuilder('mos');

        $qb
            ->join('mos.channel', 'ch')
            ->where('ch.enabled = true')
            ->orderBy('mos.id');

        return $this->aclHelper->apply($qb)->getResult();
    }
}
