<?php

namespace Oro\Bundle\ConsentBundle\Queue;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Psr\Log\LoggerInterface;

/**
 * Use this queue to store consentAcceptances alongside the tracked entity.
 * It will help us solve the case when we must wait on some doctrine lifecycle event before
 * we have a possibility to save consentAcceptances.
 */
class DelayedConsentAcceptancePersistQueue implements DelayedConsentAcceptancePersistQueueInterface
{
    /**
     * @var object[]
     * [
     *    'trackedEntityIndex' => 'trackedEntity',
     *    ...
     * ]
     */
    private $trackedEntityQueue = [];

    /**
     * @var ConsentAcceptance[][]
     * [
     *    'trackedEntityIndex' => [
     *        'consentAcceptance1',
     *        'consentAcceptance2',
     *        ...
     *     ],
     *     ...
     * ]
     */
    private $consentAcceptances = [];

    /** @var string */
    private $supportedEntityClassName;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setSupportedEntityClassName($trackedEntityClassName)
    {
        $this->supportedEntityClassName = $trackedEntityClassName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEntitySupported($trackedEntity)
    {
        return is_a($trackedEntity, $this->supportedEntityClassName);
    }

    /**
     * {@inheritdoc}
     */
    public function addConsentAcceptances($trackedEntity, array $consentAcceptances)
    {
        if (!$this->isValidEntity($trackedEntity)) {
            return false;
        }

        $trackedEntityKey = array_search($trackedEntity, $this->trackedEntityQueue, true);
        if (false !== $trackedEntityKey) {
            $this->consentAcceptances[$trackedEntityKey] = array_values(
                array_unique(
                    array_merge($this->consentAcceptances[$trackedEntityKey], $consentAcceptances),
                    SORT_REGULAR
                )
            );
        } else {
            $trackedEntityKey = spl_object_hash($trackedEntity);
            $this->trackedEntityQueue[$trackedEntityKey] = $trackedEntity;
            $this->consentAcceptances[$trackedEntityKey] = $consentAcceptances;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsentAcceptancesByTrackedEntity($trackedEntity)
    {
        if (!$this->isValidEntity($trackedEntity)) {
            return [];
        }

        if (empty($this->consentAcceptances)) {
            return [];
        }

        $trackedEntityKey = array_search($trackedEntity, $this->trackedEntityQueue, true);
        if (false === $trackedEntityKey) {
            return [];
        }

        return $this->consentAcceptances[$trackedEntityKey];
    }

    /**
     * {@inheritdoc}
     */
    public function removeConsentAcceptancesByTrackedEntity($trackedEntity)
    {
        if (!$this->isValidEntity($trackedEntity)) {
            return false;
        }

        $trackedEntityKey = array_search($trackedEntity, $this->trackedEntityQueue, true);
        if (false === $trackedEntityKey) {
            return false;
        }

        unset($this->trackedEntityQueue[$trackedEntityKey]);
        unset($this->consentAcceptances[$trackedEntityKey]);

        return true;
    }

    /**
     * @param object|null $trackedEntity
     *
     * @return bool
     */
    private function isValidEntity($trackedEntity)
    {
        if (!is_object($trackedEntity)) {
            return false;
        }

        if (!$this->isEntitySupported($trackedEntity)) {
            $this->logger->warning(
                sprintf(
                    'Expected that argument $trackedEntity will be instance of "%s", but got "%s".',
                    $this->supportedEntityClassName,
                    get_class($trackedEntity)
                )
            );
            return false;
        }

        return true;
    }
}
