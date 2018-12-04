<?php

namespace Oro\Bundle\ConsentBundle\Queue;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;

/**
 * Describes generic methods of queue that is used to store consentAcceptances alongside the tracked entity.
 * It will help us solve the case when we must wait on some doctrine lifecycle event before
 * we have a possibility to save consentAcceptances.
 */
interface DelayedConsentAcceptancePersistQueueInterface
{
    /**
     * @param string $trackedEntityClassName
     *
     * @return $this
     */
    public function setSupportedEntityClassName($trackedEntityClassName);

    /**
     * @param object $trackedEntity
     *
     * @return bool
     */
    public function isEntitySupported($trackedEntity);

    /**
     * @param object              $trackedEntity
     * @param ConsentAcceptance[] $consentAcceptances
     *
     * @return bool
     */
    public function addConsentAcceptances($trackedEntity, array $consentAcceptances);

    /**
     * @param object $trackedEntity
     *
     * @return array|ConsentAcceptance[]
     */
    public function getConsentAcceptancesByTrackedEntity($trackedEntity);

    /**
     * @param object $trackedEntity
     *
     * @return bool
     */
    public function removeConsentAcceptancesByTrackedEntity($trackedEntity);
}
