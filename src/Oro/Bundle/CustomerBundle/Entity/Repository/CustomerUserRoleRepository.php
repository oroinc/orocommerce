<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerUserRoleRepository extends EntityRepository
{
    /**
     * @param Website $website
     * @return CustomerUserRole|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDefaultCustomerUserRoleByWebsite(Website $website)
    {
        $qb = $this->createQueryBuilder('CustomerUserRole');

        return $qb
            ->innerJoin('CustomerUserRole.websites', 'website')
            ->andWhere($qb->expr()->eq('website', ':website'))
            ->setParameter('website', $website)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Checks is role default for website
     *
     * @param CustomerUserRole $role
     * @return bool
     */
    public function isDefaultForWebsite(CustomerUserRole $role)
    {
        $qb = $this->createQueryBuilder('CustomerUserRole');
        $findResult = $qb
            ->select('CustomerUserRole.id')
            ->innerJoin('CustomerUserRole.websites', 'website')
            ->where($qb->expr()->eq('CustomerUserRole', ':CustomerUserRole'))
            ->setParameter('CustomerUserRole', $role)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }

    /**
     * Checks if there are at least one user assigned to the given role
     *
     * @param CustomerUserRole $role
     * @return bool
     */
    public function hasAssignedUsers(CustomerUserRole $role)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $findResult = $qb
            ->select('accountUser.id')
            ->from('OroCustomerBundle:AccountUser', 'accountUser')
            ->innerJoin('accountUser.roles', 'CustomerUserRole')
            ->where($qb->expr()->eq('CustomerUserRole', ':CustomerUserRole'))
            ->setParameter('CustomerUserRole', $role)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }

    /**
     * Return array of assigned users to the given role
     *
     * @param CustomerUserRole $role
     * @return AccountUser[]
     */
    public function getAssignedUsers(CustomerUserRole $role)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $findResult = $qb
            ->select('accountUser')
            ->from('OroCustomerBundle:AccountUser', 'accountUser')
            ->innerJoin('accountUser.roles', 'CustomerUserRole')
            ->where($qb->expr()->eq('CustomerUserRole', ':CustomerUserRole'))
            ->setParameter('CustomerUserRole', $role)
            ->getQuery()
            ->getResult();

        return $findResult;
    }

    /**
     * @param OrganizationInterface $organization
     * @param mixed                 $account
     * @param bool                  $onlySelfManaged
     * @return QueryBuilder
     */
    public function getAvailableRolesByAccountUserQueryBuilder(
        OrganizationInterface $organization,
        $account,
        $onlySelfManaged = false
    ) {
        if ($account instanceof Account) {
            $account = $account->getId();
        }

        $qb = $this->createQueryBuilder('CustomerUserRole');

        $expr = $qb->expr()->isNull('CustomerUserRole.account');
        if ($account) {
            $expr = $qb->expr()->orX(
                $expr,
                $qb->expr()->eq('CustomerUserRole.account', ':account')
            );
            $qb->setParameter('account', (int)$account);
        }

        if ($onlySelfManaged) {
            $qb->where(
                $qb->expr()->andX(
                    $expr,
                    $qb->expr()->eq('CustomerUserRole.selfManaged', ':selfManaged'),
                    $qb->expr()->eq('CustomerUserRole.organization', ':organization')
                )
            );
            $qb->setParameter('selfManaged', true, \PDO::PARAM_BOOL);
        } else {
            $qb->where(
                $qb->expr()->andX(
                    $expr,
                    $qb->expr()->eq('CustomerUserRole.organization', ':organization')
                )
            );
        }

        $qb->setParameter('organization', $organization);

        return $qb;
    }

    /**
     * @param OrganizationInterface $organization
     * @param mixed $account
     * @return QueryBuilder
     */
    public function getAvailableSelfManagedRolesByAccountUserQueryBuilder(OrganizationInterface $organization, $account)
    {
        return $this->getAvailableRolesByAccountUserQueryBuilder($organization, $account, true);
    }
}
