<?php

namespace Oro\Bundle\ConsentBundle\EventListener;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Helper\GuestCustomerConsentAcceptancesHelper;
use Oro\Bundle\ConsentBundle\Queue\DelayedConsentAcceptancePersistQueueInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RFPBundle\Entity\Request;

/**
 * Listener that helps to make persist on ConsentAcceptance objects
 * that can't be persisted within postSubmit event,
 * because of RFQ didn't contain data in field CustomerUser.
 */
class RfqEntityListener
{
    /** @var DelayedConsentAcceptancePersistQueueInterface */
    private $persistQueue;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var CustomerUserExtractor */
    private $customerUserExtractor;

    /** @var GuestCustomerConsentAcceptancesHelper */
    private $guestCustomerHelper;

    /**
     * @param DelayedConsentAcceptancePersistQueueInterface $delayedConsentAcceptancePersistQueue
     * @param DoctrineHelper $doctrineHelper
     * @param CustomerUserExtractor $customerUserExtractor
     * @param GuestCustomerConsentAcceptancesHelper $guestCustomerHelper
     */
    public function __construct(
        DelayedConsentAcceptancePersistQueueInterface $delayedConsentAcceptancePersistQueue,
        DoctrineHelper $doctrineHelper,
        CustomerUserExtractor $customerUserExtractor,
        GuestCustomerConsentAcceptancesHelper $guestCustomerHelper
    ) {
        $this->persistQueue = $delayedConsentAcceptancePersistQueue;
        $this->doctrineHelper = $doctrineHelper;
        $this->customerUserExtractor = $customerUserExtractor;
        $this->guestCustomerHelper = $guestCustomerHelper;
    }

    /**
     * @param Request $rfq
     */
    public function persistApplicableConsentAcceptance(Request $rfq)
    {
        $customerUser = $this->customerUserExtractor->extract($rfq);
        if (!$customerUser instanceof CustomerUser) {
            return;
        }

        $consentAcceptanceOnPersist = $this->persistQueue->getConsentAcceptancesByTrackedEntity($rfq);
        $consentAcceptanceOnPersist = $this->guestCustomerHelper->filterGuestCustomerAcceptances(
            $customerUser,
            $consentAcceptanceOnPersist
        );
        if (empty($consentAcceptanceOnPersist)) {
            return;
        }

        $this->persistQueue->removeConsentAcceptancesByTrackedEntity($rfq);

        $em = $this->doctrineHelper->getEntityManagerForClass(ConsentAcceptance::class);

        foreach ($consentAcceptanceOnPersist as $consentAcceptance) {
            /**
             * CustomerUser is created on prePersist event,
             * here is one of the valid places where we can set it to consentAcceptance
             */
            $consentAcceptance->setCustomerUser($customerUser);
            $em->persist($consentAcceptance);
        }

        $unitOfWork = $em->getUnitOfWork();
        $unitOfWork->computeChangeSets();
    }
}
