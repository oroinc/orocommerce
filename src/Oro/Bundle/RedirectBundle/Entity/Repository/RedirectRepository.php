<?php

namespace Oro\Bundle\RedirectBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
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
     * @param Organization|null $organization An organization the redirect should belong to
     * @return null|Redirect
     */
    public function findByUrl($from, ?ScopeCriteria $scopeCriteria = null, ?Organization $organization = null)
    {
        $qb = $this->createQueryBuilder('redirect');
        $qb->leftJoin('redirect.scopes', 'scope', Join::WITH)
            ->where($qb->expr()->eq('redirect.fromHash', ':fromHash'))
            ->andWhere($qb->expr()->eq('redirect.from', ':fromUrl'))
            ->setParameters([
                'fromHash' => md5($from),
                'fromUrl' => $from
            ]);
        $this->applyOrganizationRestriction($qb, $organization);

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
     * @param Organization|null $organization An organization the redirect should belong to
     * @return null|Redirect
     */
    public function findByPrototype($from, ScopeCriteria $scopeCriteria, ?Organization $organization = null)
    {
        $qb = $this->createQueryBuilder('redirect');
        $qb->leftJoin('redirect.scopes', 'scope', Join::WITH)
            ->where($qb->expr()->eq('redirect.fromPrototype', ':fromPrototype'))
            ->setParameter('fromPrototype', $from);
        $this->applyOrganizationRestriction($qb, $organization);

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

    private function applyOrganizationRestriction(QueryBuilder $qb, ?Organization $organization): void
    {
        if (null === $organization) {
            return;
        }

        $qb->leftJoin('redirect.slug', 'redirectSlug')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('redirect.slug'),
                $qb->expr()->eq('redirectSlug.organization', ':organization')
            ))
            ->setParameter('organization', $organization);
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
