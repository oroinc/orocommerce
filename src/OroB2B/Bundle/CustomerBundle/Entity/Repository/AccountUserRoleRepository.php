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
        return $this->createQueryBuilder('accountUserRole')
            ->innerJoin('accountUserRole.websites', 'website')
            ->andWhere('website = :website')
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
        $findResult = $this->createQueryBuilder('accountUserRole')
            ->innerJoin('accountUserRole.websites', 'website')
            ->select('accountUserRole.id')
            ->where('accountUserRole = :accountUserRole')
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
        $findResult = $this->_em->createQueryBuilder()
            ->select('accountUser')
            ->from('OroB2BCustomerBundle:AccountUser', 'accountUser')
            ->join('accountUser.roles', 'accountUserRole')
            ->where('accountUserRole = :accountUserRole')
            ->setParameter('accountUserRole', $role)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }
}
