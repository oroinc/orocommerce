<?php

namespace Oro\Bundle\ProductBundle\Handler;

use Oro\Bundle\ProductBundle\Exception\FailedToRunReindexProductCollectionJobException;

/**
 * This interface must be implemented by service that handles the logic of processing product collection
 * after segment(s) change(s), which uses to prepare all required jobs and send all required messages to MQ.
 */
interface AsyncReindexProductCollectionHandlerInterface
{
    /**
     * Return 'true' in case when unique job successfully run and
     * reindex product collection child jobs and dependent job scheduled,
     * 'false' when failed to run job or throws exception.
     *
     * @param iterable $childJobPartialMessages
     * @param string $uniqueJobName
     * @param bool $throwExceptionOnFailToRunJob - throws exception when failed to run job
     * @param array|null $indexationFieldGroups - optional list of field groups for indexation
     * @return bool
     * @throws FailedToRunReindexProductCollectionJobException
     */
    public function handle(
        iterable $childJobPartialMessages,
        string $uniqueJobName,
        bool $throwExceptionOnFailToRunJob = false,
        array $indexationFieldGroups = null
    ): bool;
}
