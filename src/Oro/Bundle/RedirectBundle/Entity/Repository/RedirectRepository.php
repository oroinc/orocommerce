<?php

namespace Oro\Bundle\RedirectBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class RedirectRepository extends EntityRepository
{
    /**
     * @param string $from
     * @param ScopeCriteria|null $scopeCriteria
     * @return null|Redirect
     */
    public function findByUrl($from, ScopeCriteria $scopeCriteria = null)
    {
        $qb = $this->createQueryBuilder('redirect');
        $qb->leftJoin('redirect.scopes', 'scope', Join::WITH)
            ->where($qb->expr()->eq('redirect.fromHash', ':fromHash'))
            ->andWhere($qb->expr()->eq('redirect.from', ':fromUrl'))
            ->setParameters([
                'fromHash' => md5($from),
                'fromUrl' => $from
            ]);

        if ($scopeCriteria) {
            $qb->addSelect('scope.id as matchedScopeId');
            $scopeCriteria->applyToJoinWithPriority($qb, 'scope');

            $results = $qb->getQuery()->getResult();
            foreach ($results as $result) {
                /** @var Redirect $redirect */
                $redirect = $result[0];
                $matchedScopeId = $result['matchedScopeId'];
                if ($matchedScopeId || $redirect->getScopes()->isEmpty()) {
                    return $redirect;
                }
            }
        } else {
            $qb->andWhere($qb->expr()->isNull('scope.id'));

            return $qb->getQuery()->getOneOrNullResult();
        }

        return null;
    }
}
