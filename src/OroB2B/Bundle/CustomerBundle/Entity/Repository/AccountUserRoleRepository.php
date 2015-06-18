<?php

namespace OroB2B\Bundle\CustomerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountUserRoleRepository extends EntityRepository
{
    /**
     * @param Website $website
     * @return AccountUserRole|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDefaultAccountUserRoleByWebsite(Website $website)
    {
        $qb = $this->createQueryBuilder('accountUserRole');
        return $qb
            ->innerJoin('accountUserRole.websites', 'website')
            ->andWhere($qb->expr()->eq('website', ':website'))
            ->setParameter('website', $website)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Checks is role default for website
     *
     * @param AccountUserRole $role
     * @return bool
     */
    public function isDefaultForWebsite(AccountUserRole $role)
    {
        $qb = $this->createQueryBuilder('accountUserRole');
        $findResult = $qb
            ->select('accountUserRole.id')
            ->innerJoin('accountUserRole.websites', 'website')
            ->where($qb->expr()->eq('accountUserRole', ':accountUserRole'))
            ->setParameter('accountUserRole', $role)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }

    /**
     * Checks if there are at least one user assigned to the given role
     *
     * @param AccountUserRole $role
     * @return bool
     */
    public function hasAssignedUsers(AccountUserRole $role)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $findResult = $qb
            ->select('accountUser.id')
            ->from('OroB2BCustomerBundle:AccountUser', 'accountUser')
            ->innerJoin('accountUser.roles', 'accountUserRole')
            ->where($qb->expr()->eq('accountUserRole', ':accountUserRole'))
            ->setParameter('accountUserRole', $role)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }
}
