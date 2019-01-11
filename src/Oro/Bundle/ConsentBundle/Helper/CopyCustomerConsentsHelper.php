<?php

namespace Oro\Bundle\ConsentBundle\Helper;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * This helper allows copying signed consents from one customerUser to another
 */
class CopyCustomerConsentsHelper
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param CustomerUser $sourceCustomerUser
     * @param CustomerUser $targetCustomerUser
     */
    public function copyConsentsIfTargetHasNoAcceptedConsents(
        CustomerUser $sourceCustomerUser,
        CustomerUser $targetCustomerUser
    ) {
        /** @var $consentAcceptanceRepository ConsentAcceptanceRepository */
        $consentAcceptanceRepository = $this->getEntityManager()->getRepository(ConsentAcceptance::class);
        $sourceCustomerUserConsentAcceptances = $consentAcceptanceRepository->getAcceptedConsentsByCustomer(
            $sourceCustomerUser
        );

        /**
         * Nothing to copy
         */
        if (0 === count($sourceCustomerUserConsentAcceptances)) {
            return;
        }

        $targetHasNoConsentAcceptances = !$targetCustomerUser->getId() ||
            0 === count(
                $consentAcceptanceRepository->getAcceptedConsentsByCustomer(
                    $targetCustomerUser
                )
            );

        if (!$targetHasNoConsentAcceptances) {
            return;
        }

        /**
         * Check that target has no accepted consent on insert
         */
        $entitiesOnInsert = $this->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions();
        $consentAcceptanceEntitiesOnInsert = array_filter(
            $entitiesOnInsert,
            function ($entity) use ($targetCustomerUser) {
                return $entity instanceof ConsentAcceptance && $entity->getCustomerUser() === $targetCustomerUser;
            }
        );

        if (0 !== count($consentAcceptanceEntitiesOnInsert)) {
            return;
        }

        foreach ($sourceCustomerUserConsentAcceptances as $sourceConsentAcceptance) {
            /** @var $consentAcceptance ConsentAcceptance */
            $consentAcceptance = $this->doctrineHelper->createEntityInstance(ConsentAcceptance::class);
            $consentAcceptance->setCustomerUser($targetCustomerUser);
            $consentAcceptance->setConsent($sourceConsentAcceptance->getConsent());

            if ($sourceConsentAcceptance->getLandingPage() instanceof Page) {
                $consentAcceptance->setLandingPage($sourceConsentAcceptance->getLandingPage());
            }

            $this->getEntityManager()->persist($consentAcceptance);
        }
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManager(ConsentAcceptance::class);
    }
}
