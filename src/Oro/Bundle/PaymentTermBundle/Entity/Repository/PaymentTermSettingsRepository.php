<?php

namespace Oro\Bundle\PaymentTermBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;

class PaymentTermSettingsRepository extends EntityRepository
{
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

        return $qb->getQuery()->getResult();
    }
}
