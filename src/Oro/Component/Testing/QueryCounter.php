<?php

declare(strict_types=1);

namespace Oro\Component\Testing;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Counts the database queries executed by a callback.
 *
 * Unlike {@see QueryTracker} it does not keep the executed SQL, which is what makes it usable
 * for a query parametrized with an array: QueryAnalyzer casts the query parameters to a string.
 */
class QueryCounter implements SQLLogger
{
    private int $count = 0;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function countQueries(callable $callback): int
    {
        $configuration = $this->em->getConnection()->getConfiguration();
        $previousLogger = $configuration->getSQLLogger();
        $configuration->setSQLLogger($this);
        $this->count = 0;
        try {
            $callback();
        } finally {
            $configuration->setSQLLogger($previousLogger);
        }

        return $this->count;
    }

    #[\Override]
    public function startQuery($sql, ?array $params = null, ?array $types = null): void
    {
        $this->count++;
    }

    #[\Override]
    public function stopQuery(): void
    {
    }
}
