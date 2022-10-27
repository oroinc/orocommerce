<?php

namespace Oro\Bundle\ConsentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

/**
 * Doctrine repository for ConsentAcceptance entity
 */
class ConsentAcceptanceRepository extends EntityRepository
{
    /**
     * @param CustomerUser $customerUser
     *
     * @return ConsentAcceptance[]
     */
    public function getAcceptedConsentsByCustomer(CustomerUser $customerUser)
    {
        $qb = $this->createQueryBuilder('ca');
        $qb->leftJoin('ca.customerUser', 'customer_user');
        $qb->andWhere('ca.customerUser = :customerUser')
            ->setParameter('customerUser', $customerUser);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Page $page
     *
     * @return bool
     */
    public function hasLandingPageAcceptedConsents(Page $page)
    {
        $qb = $this->createQueryBuilder('ca');
        $qb
            ->select($qb->expr()->count('ca.id'))
            ->andWhere('ca.landingPage = :page')
            ->setParameter('page', $page);

        return (bool) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Consent $consent
     *
     * @return bool
     */
    public function hasConsentAcceptancesByConsent(Consent $consent)
    {
        $qb = $this->createQueryBuilder('ca');
        $qb
            ->select($qb->expr()->count('ca.id'))
            ->andWhere('ca.consent = :consent')
            ->setParameter('consent', $consent);

        return (bool) $qb->getQuery()->getSingleScalarResult();
    }
}
