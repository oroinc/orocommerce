<?php

namespace Oro\Bundle\PaymentTermBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Doctrine repository for PaymentTermSettings entity
 */
class PaymentTermSettingsRepository extends ServiceEntityRepository
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
     * @return PaymentTermSettings[]
     */
    public function findWithEnabledChannel()
    {
        $qb = $this->createQueryBuilder('pts');

        $qb
            ->join('pts.channel', 'ch')
            ->where('ch.enabled = true')
            ->orderBy('pts.id');

        return $this->aclHelper->apply($qb)->getResult();
    }
}
