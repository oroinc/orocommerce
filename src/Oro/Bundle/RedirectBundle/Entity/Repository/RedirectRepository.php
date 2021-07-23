<?php

namespace Oro\Bundle\RedirectBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Doctrine repository for Oro\Bundle\RedirectBundle\Entity\Redirect entity.
 */
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

    /**
     * @param string $from
     * @param ScopeCriteria $scopeCriteria
     * @return null|Redirect
     */
    public function findByPrototype($from, ScopeCriteria $scopeCriteria)
    {
        $qb = $this->createQueryBuilder('redirect');
        $qb->leftJoin('redirect.scopes', 'scope', Join::WITH)
            ->where($qb->expr()->eq('redirect.fromPrototype', ':fromPrototype'))
            ->setParameter('fromPrototype', $from);

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

        return null;
    }

    public function updateRedirectsBySlug(Slug $slug)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update($this->getEntityName(), 'redirect')
            ->set('redirect.to', ':newUrl')
            ->where($qb->expr()->eq('redirect.slug', ':slug'))
            ->setParameter('newUrl', $slug->getUrl())
            ->setParameter('slug', $slug);

        $qb->getQuery()->execute();
    }

    public function deleteCyclicRedirects(Slug $slug)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'redirect')
            ->where($qb->expr()->eq('redirect.slug', ':slug'))
            ->andWhere($qb->expr()->eq('redirect.from', 'redirect.to'))
            ->setParameter('slug', $slug);

        $qb->getQuery()->execute();
    }
}
