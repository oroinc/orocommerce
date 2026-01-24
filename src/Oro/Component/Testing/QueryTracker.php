<?php

namespace Oro\Component\Testing;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

/**
 * Tracks database queries executed during test execution.
 *
 * This class intercepts SQL queries executed through Doctrine by installing a custom SQL logger
 * on the database connection. It allows tests to verify the number and nature of queries executed,
 * helping identify performance issues and unintended database access patterns.
 */
class QueryTracker
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var QueryAnalyzer
     */
    protected $queryAnalyzer;

    /**
     * @var SQLLogger
     */
    protected $previousLogger;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->queryAnalyzer = new QueryAnalyzer($em->getConnection()->getDatabasePlatform());
    }

    public function start()
    {
        $this->previousLogger = $this->em->getConnection()->getConfiguration()->getSQLLogger();
        $this->em->getConnection()->getConfiguration()->setSQLLogger($this->queryAnalyzer);
    }

    public function stop()
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger($this->previousLogger);
    }

    /**
     * @return array
     */
    public function getExecutedQueries()
    {
        return $this->queryAnalyzer->getExecutedQueries();
    }
}
