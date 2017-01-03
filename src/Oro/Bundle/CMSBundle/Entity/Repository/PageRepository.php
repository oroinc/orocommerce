<?php

namespace Oro\Bundle\CMSBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class PageRepository extends EntityRepository
{
    /**
     * @param string $title
     * @param Localization $localization
     * @return null|Page
     */
    public function findOneByTitle($title, Localization $localization = null)
    {
        $qb = $this->createQueryBuilder('page');

        if ($localization === null) {
            $joinExpr = $qb->expr()->isNull('title.localization');
        } else {
            $joinExpr = $qb->expr()->eq('title.localization', ':localization');
            $qb->setParameter('localization', $localization);
        }
        return $qb
            ->select('partial page.{id}')
            ->innerJoin('page.titles', 'title', Join::WITH, $joinExpr)
            ->andWhere('title.string = :title')
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
