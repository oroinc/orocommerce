<?php

namespace Oro\Bundle\CMSBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CMSBundle\Entity\Page;

class PageRepository extends EntityRepository
{
    /**
     * @param string $title
     * @return Page|null
     */
    public function findOneByDefaultTitle($title)
    {
        $qb = $this->createQueryBuilder('page');

        return $qb
            ->select('partial page.{id}')
            ->innerJoin('page.titles', 'title', Join::WITH, $qb->expr()->isNull('title.localization'))
            ->andWhere('title.string = :title')
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
