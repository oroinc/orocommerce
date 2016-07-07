<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorInterface;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorTrait;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class AccountGroupRepository extends EntityRepository implements BatchIteratorInterface
{
    use BatchIteratorTrait;

    /**
     * @param string $name
     *
     * @return null|AccountGroup
     */
    public function findOneByName($name)
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * @param Category $category
     * @param string $visibility
     * @param array $restrictedAccountGroupIds
     * @return array
     */
    public function getCategoryAccountGroupIdsByVisibility(
        Category $category,
        $visibility,
        array $restrictedAccountGroupIds = null
    ) {
        $qb = $this->createQueryBuilder('accountGroup');

        $qb->select('accountGroup.id')
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
                'AccountGroupCategoryVisibility',
                Join::WITH,
                $qb->expr()->eq('AccountGroupCategoryVisibility.accountGroup', 'accountGroup')
            )
            ->where($qb->expr()->eq('AccountGroupCategoryVisibility.category', ':category'))
            ->andWhere($qb->expr()->eq('AccountGroupCategoryVisibility.visibility', ':visibility'))
            ->setParameters([
                'category' => $category,
                'visibility' => $visibility
            ]);

        if ($restrictedAccountGroupIds !== null) {
            $qb->andWhere($qb->expr()->in('accountGroup.id', ':restrictedAccountGroupIds'))
            ->setParameter('restrictedAccountGroupIds', $restrictedAccountGroupIds);
        }

        // Return only account group ids
        return array_map('current', $qb->getQuery()->getScalarResult());
    }
}
