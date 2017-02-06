<?php

namespace Oro\Bundle\RedirectBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class RedirectRepository extends EntityRepository
{
    /**
     * @param string $from
     * @param Scope $scope
     * @return array|Redirect[]
     */
    public function findByFrom($from, Scope $scope)
    {
        $qb = $this->createQueryBuilder('redirect');
        $qb->innerJoin('redirect.scopes', 'scopes', Join::WITH)
        ->where(
            $qb->expr()->andX(
                $qb->expr()->eq('redirect.fromHash', ':fromHash'),
                $qb->expr()->eq('redirect.from', ':fromUrl')
            )
        )
        ->andWhere(':scope MEMBER OF redirect.scopes')
        ->setMaxResults(1)
        ->setParameters([
            'fromHash' => md5($from),
            'fromUrl' => $from,
            'scope' => $scope
        ]);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
