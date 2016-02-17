<?php

namespace OroB2B\Bundle\AccountBundle\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class SoftDeleteableWalkerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testInQueryBuilder()
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityManager $objectManager */
        $objectManager = $registry->getManager();

        /** todo refactor analyser after BB-1561 */
        $queryAnalyzer = new QueryAnalyzer($objectManager->getConnection()->getDatabasePlatform());
        $previousLogger = $objectManager->getConnection()->getConfiguration()->getSQLLogger();

        $objectManager->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        $filters = $objectManager->getFilters();
            $filters->enable('soft_deleteable');

        /** @var EntityRepository $repository */
        $repository = $objectManager->getRepository('OroB2BRFPBundle:Request');

        $q = $repository->createQueryBuilder('r')
            ->select('r.id')
            ->getQuery()
            ->execute();

        $queries = $queryAnalyzer->getExecutedQueries();
        $objectManager->getConnection()->getConfiguration()->setSQLLogger($previousLogger);

//        $q->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['OroB2B\Bundle\AccountBundle\Doctrine\SoftDeleteableWalker']);
    }
}
