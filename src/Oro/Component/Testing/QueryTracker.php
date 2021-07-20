<?php

namespace Oro\Component\Testing;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

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
