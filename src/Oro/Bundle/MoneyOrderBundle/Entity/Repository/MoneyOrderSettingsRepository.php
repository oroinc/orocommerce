<?php

namespace Oro\Bundle\MoneyOrderBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;

class MoneyOrderSettingsRepository extends EntityRepository
{
    /**
     * @return MoneyOrderSettings[]
     */
    public function findWithEnabledChannel()
    {
        $qb = $this->createQueryBuilder('mos');

        $qb
            ->join('mos.channel', 'ch')
            ->where('ch.enabled = true');

        return $qb->getQuery()->getResult();
    }
}
