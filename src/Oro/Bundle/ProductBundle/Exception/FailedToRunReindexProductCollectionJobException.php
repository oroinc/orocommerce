<?php

namespace Oro\Bundle\ProductBundle\Exception;

/**
 * Exception encountered during scheduling of reindexing product collection
 */
class FailedToRunReindexProductCollectionJobException extends \RuntimeException
{
    public static function create(string $uniqueJobName): static
    {
        return new static(
            sprintf(
                'Failed to run unique job "%s" to process reindex product collection, ' .
                'probably the same job is in progress right now.',
                $uniqueJobName
            )
        );
    }
}
