<?php

namespace Oro\Bundle\FrontendNavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\FrontendNavigationBundle\Entity\MenuUpdate;

class MenuUpdateRepository extends EntityRepository
{
    /**
     * @param string $menu
     * @param Organization|null $organization
     * @param Account|null $account
     * @param AccountUser|null $accountUser
     * @param Website|null $website
     *
     * @return MenuUpdate[]
     *
     * @throws \BadMethodCallException
     */
    public function getUpdates(
        $menu,
        Organization $organization = null,
        Account $account = null,
        AccountUser $accountUser = null,
        Website $website = null
    ) {
        $qb = $this->createQueryBuilder('mu')
            ->leftJoin('mu.website', 'ws');

        $exprs = [
            $qb->expr()->andX(
                $qb->expr()->eq('mu.ownershipType', MenuUpdate::OWNERSHIP_GLOBAL),
                $qb->expr()->isNull('mu.ownerId')
            )
        ];

        if ($organization !== null) {
            $exprs[] = $qb->expr()->andX(
                $qb->expr()->eq('mu.ownershipType', MenuUpdate::OWNERSHIP_ORGANIZATION),
                $qb->expr()->eq('mu.ownerId', $organization->getId())
            );
        }

        if ($account !== null) {
            if ($website === null) {
                throw new \BadMethodCallException('You should specify $website when using account scope');
            }

            $exprs[] = $qb->expr()->andX(
                $qb->expr()->eq('mu.ownershipType', MenuUpdate::OWNERSHIP_ACCOUNT),
                $qb->expr()->eq('mu.ownerId', $account->getId()),
                $qb->expr()->eq('ws.id', $website->getId())
            );
        }

        if ($accountUser !== null) {
            if ($website === null) {
                throw new \BadMethodCallException('You should specify $website when using account user scope');
            }

            $exprs[] = $qb->expr()->andX(
                $qb->expr()->eq('mu.ownershipType', MenuUpdate::OWNERSHIP_ACCOUNT_USER),
                $qb->expr()->eq('mu.ownerId', $accountUser->getId()),
                $qb->expr()->eq('ws.id', $website->getId())
            );
        }

        $qb->where($qb->expr()->andX(
            $qb->expr()->eq('mu.menu', ':menu'),
            call_user_func_array([$qb->expr(), 'orX'], $exprs)
        ));

        return $qb
            ->orderBy('mu.ownershipType', 'asc')
            ->addOrderBy('ws.id', 'asc')
            ->addOrderBy('mu.ownerId', 'asc')
            ->setParameter('menu', $menu)
            ->getQuery()
            ->getResult();
    }
}
