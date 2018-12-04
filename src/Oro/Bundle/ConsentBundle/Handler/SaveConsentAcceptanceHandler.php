<?php

namespace Oro\Bundle\ConsentBundle\Handler;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Queue\DelayedConsentAcceptancePersistQueueInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Logic that does update of customer consent acceptances.
 * (Please note that this service doesn't do flush and it expects that flush will be triggered by main form handler)
 */
class SaveConsentAcceptanceHandler
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var DelayedConsentAcceptancePersistQueueInterface */
    private $delayedPersistQueue;

    /** @var ConsentAcceptanceProvider */
    private $consentAcceptanceProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param DelayedConsentAcceptancePersistQueueInterface $delayedPersistQueue
     * @param ConsentAcceptanceProvider $consentAcceptanceProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        DelayedConsentAcceptancePersistQueueInterface $delayedPersistQueue,
        ConsentAcceptanceProvider $consentAcceptanceProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->delayedPersistQueue = $delayedPersistQueue;
        $this->consentAcceptanceProvider = $consentAcceptanceProvider;
    }

    /**
     * @param CustomerUser $customerUser
     * @param ConsentAcceptance[] $currentConsentAcceptances (All accepted consent acceptances)
     */
    public function save(CustomerUser $customerUser, array $currentConsentAcceptances)
    {
        $consentAcceptancesOnDelayedPersist = [];

        $em = $this->doctrineHelper->getEntityManagerForClass(ConsentAcceptance::class);
        $isCustomerUserTrackedByEm = $customerUser->getId() || $em->contains($customerUser);

        $consentAcceptancesOnInsert = $this->getConsentAcceptanceOnInsert($currentConsentAcceptances);
        foreach ($consentAcceptancesOnInsert as $consentAcceptance) {
            /**
             * Filling the customerUser property in ConsentAcceptance,
             * because DataTransformer creates ConsentAcceptance without filling this property.
             * Must be applied to the new entity only.
             */
            $consentAcceptance->setCustomerUser($customerUser);

            if ($isCustomerUserTrackedByEm) {
                $em->persist($consentAcceptance);
            } else {
                $consentAcceptancesOnDelayedPersist[] = $consentAcceptance;
            }
        }

        if ([] !== $consentAcceptancesOnDelayedPersist) {
            $this->delayedPersistQueue->addConsentAcceptances(
                $customerUser,
                $consentAcceptancesOnDelayedPersist
            );
        }

        if ($customerUser->getId()) {
            $consentAcceptancesOnDelete = $this->getConsentAcceptanceOnDelete($currentConsentAcceptances);
            foreach ($consentAcceptancesOnDelete as $consentAcceptance) {
                $em->remove($consentAcceptance);
            }
        }
    }

    /**
     * @param ConsentAcceptance[] $consentAcceptances
     *
     * @return ConsentAcceptance[]
     */
    private function getConsentAcceptanceOnInsert(array $consentAcceptances)
    {
        return array_filter(
            $consentAcceptances,
            function (ConsentAcceptance $consentAcceptance) {
                return null === $consentAcceptance->getId();
            }
        );
    }

    /**
     * @param ConsentAcceptance[] $consentAcceptances
     *
     * @return ConsentAcceptance[]
     */
    private function getConsentAcceptanceOnDelete(array $consentAcceptances)
    {
        $customerAcceptances = $this->consentAcceptanceProvider->getCustomerConsentAcceptances();
        return array_filter(
            $customerAcceptances,
            function (ConsentAcceptance $consentAcceptance) use ($consentAcceptances) {
                return !in_array($consentAcceptance, $consentAcceptances, true);
            }
        );
    }
}
