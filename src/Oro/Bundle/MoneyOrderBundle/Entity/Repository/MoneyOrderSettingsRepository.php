<?php

namespace Oro\Bundle\MoneyOrderBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class MoneyOrderSettingsRepository extends EntityRepository
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
