<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class CustomerRepository extends EntityRepository
{
    /**
     * @param AclHelper $aclHelper
     * @param int $customerId
     * @return array
     */
    public function getChildrenIds(AclHelper $aclHelper, $customerId)
    {
        $qb = $this->createQueryBuilder('customer');
        $qb->select('customer.id as customer_id')
            ->where($qb->expr()->eq('customer.parent', ':parent'))
            ->setParameter('parent', $customerId);
        $result = $aclHelper->apply($qb)->getArrayResult();
        $children = $result;

        if ($result) {
            foreach ($result as $childId) {
                $children = array_merge($children, $this->getChildrenIds($aclHelper, $childId));
            }
        }

        return $children;
    }
}
