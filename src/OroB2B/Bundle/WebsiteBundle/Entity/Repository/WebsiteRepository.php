<?php

namespace OroB2B\Bundle\WebsiteBundle\Entity\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteRepository extends EntityRepository
{
    /**
     * @return Website[]|Collection
     */
    public function getAllWebsites()
    {
        return $this->createQueryBuilder('website')
            ->addOrderBy('website.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Website
     */
    public function getDefaultWebsite()
    {
        return $this->findOneBy([], ['id' => Criteria::ASC]);
    }
}
