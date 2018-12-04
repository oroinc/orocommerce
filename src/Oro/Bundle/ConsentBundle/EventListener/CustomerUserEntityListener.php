<?php

namespace Oro\Bundle\ConsentBundle\EventListener;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Queue\DelayedConsentAcceptancePersistQueueInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Listener that helps to make persist on ConsentAcceptance objects
 * right after CustomerUser will be persisted, we use it because
 * we can't do cascade persist of CustomerUser entity from the ConsentAcceptance
 */
class CustomerUserEntityListener
{
    /**
     * @var DelayedConsentAcceptancePersistQueueInterface
     */
    private $persistQueue;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DelayedConsentAcceptancePersistQueueInterface $delayedConsentAcceptancePersistQueue
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        DelayedConsentAcceptancePersistQueueInterface $delayedConsentAcceptancePersistQueue,
        DoctrineHelper $doctrineHelper
    ) {
        $this->persistQueue = $delayedConsentAcceptancePersistQueue;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param CustomerUser $customerUser
     */
    public function persistApplicableConsentAcceptance(CustomerUser $customerUser)
    {
        $consentAcceptanceOnPersist = $this->persistQueue->getConsentAcceptancesByTrackedEntity($customerUser);
        if (empty($consentAcceptanceOnPersist)) {
            return;
        }

        $this->persistQueue->removeConsentAcceptancesByTrackedEntity($customerUser);

        $em = $this->doctrineHelper->getEntityManagerForClass(ConsentAcceptance::class);
        foreach ($consentAcceptanceOnPersist as $consentAcceptance) {
            $em->persist($consentAcceptance);
        }
    }
}
