<?php

namespace OroB2B\Bundle\MenuBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Component\Tree\Entity\Repository\NestedTreeRepository;

class MenuItemRepository extends NestedTreeRepository
{
    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getRootsQueryBuilder()
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('m')
            ->innerJoin('m.titles', 'mt', Join::WITH, $qb->expr()->isNull('mt.locale'))
            ->where($qb->expr()->isNull('m.parentMenuItem'));

        return $qb;
    }

    /**
     * @param string $title
     * @return MenuItem|null
     */
    public function findRootByDefaultTitle($title)
    {
        $qb = $this->getRootsQueryBuilder();
        $qb->andWhere($qb->expr()->eq('mt.string', ':defaultTitle'))
            ->setParameter('defaultTitle', $title)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return MenuItem[]
     */
    public function findRoots()
    {
        $qb = $this->getRootsQueryBuilder();

        return $qb->getQuery()->getResult();
    }
}
