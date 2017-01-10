<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
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
            ->select('customerUser.id')
            ->from('OroCustomerBundle:CustomerUser', 'customerUser')
            ->innerJoin('customerUser.roles', 'CustomerUserRole')
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
     * @return CustomerUser[]
     */
    public function getAssignedUsers(CustomerUserRole $role)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $findResult = $qb
            ->select('customerUser')
            ->from('OroCustomerBundle:CustomerUser', 'customerUser')
            ->innerJoin('customerUser.roles', 'CustomerUserRole')
            ->where($qb->expr()->eq('CustomerUserRole', ':CustomerUserRole'))
            ->setParameter('CustomerUserRole', $role)
            ->getQuery()
            ->getResult();

        return $findResult;
    }

    /**
     * @param OrganizationInterface $organization
     * @param mixed                 $customer
     * @param bool                  $onlySelfManaged
     * @return QueryBuilder
     */
    public function getAvailableRolesByCustomerUserQueryBuilder(
        OrganizationInterface $organization,
        $customer,
        $onlySelfManaged = false
    ) {
        if ($customer instanceof Customer) {
            $customer = $customer->getId();
        }

        $qb = $this->createQueryBuilder('CustomerUserRole');

        $expr = $qb->expr()->isNull('CustomerUserRole.customer');
        if ($customer) {
            $expr = $qb->expr()->orX(
                $expr,
                $qb->expr()->eq('CustomerUserRole.customer', ':customer')
            );
            $qb->setParameter('customer', (int)$customer);
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
     * @param mixed $customer
     * @return QueryBuilder
     */
    public function getAvailableSelfManagedRolesByCustomerUserQueryBuilder(
        OrganizationInterface $organization,
        $customer
    ) {
        return $this->getAvailableRolesByCustomerUserQueryBuilder($organization, $customer, true);
    }
}
