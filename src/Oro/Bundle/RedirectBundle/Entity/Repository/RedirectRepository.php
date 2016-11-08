<?php

namespace Oro\Bundle\RedirectBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class RedirectRepository extends EntityRepository
{
    /**
     * @param string $from
     * @param Website $website
     * @return array
     */
    public function findByFrom($from, Website $website = null)
    {
        $qb = $this->createQueryBuilder('redirect');
        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->eq('redirect.fromHash', ':fromHash'),
                $qb->expr()->eq('redirect.from', ':fromUrl')
            )
        );
        $qb->setParameters([
            'fromHash' => md5($from),
            'fromUrl' => $from
        ]);

        if ($website) {
            $qb->andWhere($qb->expr()->eq('redirect.website', ':website'))
                ->setParameter('website', $website);
        };
        
        return $qb->getQuery()->getOneOrNullResult();
    }
}
