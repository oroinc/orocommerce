<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\QueryTracker;

use OroB2B\Bundle\AccountBundle\Doctrine\SoftDeleteableFilter;
use OroB2B\Bundle\AccountBundle\Doctrine\SoftDeleteableInterface;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods).
 */
class SoftDeleteableFilterTest extends WebTestCase
{
    /**
     * @var QueryTracker
     */
    protected $queryTracker;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityRepository
     */
    protected $requestRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData'
            ]
        );

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->queryTracker = new QueryTracker($this->em);
        $this->queryTracker->start();
    }

    protected function tearDown()
    {
        $this->queryTracker->stop();
        parent::tearDown();
    }

    public function testFindMethod()
    {
        /** @var Request $request */
        $request = $this->getReferenceRepository()->getReference(LoadRequestData::REQUEST9);
        $this->em->detach($request);

        //FILTER ENABLED
        $this->enableFilter();
        $result = $this->getRepository()
            ->find($request->getId());

        $this->assertNull($result);

        //FILTER DISABLED
        $this->disableFilter();
        $result = $this->getRepository()
            ->find($request->getId());

        $this->assertNotNull($result);

        //CHECK QUERIES
        $this->checkQueries();
    }

    public function testFindByMethod()
    {
        //FILTER ENABLED
        $this->enableFilter();
        $result = $this->getRepository()
            ->findBy(['firstName' => LoadRequestData::FIRST_NAME_DELETED]);

        $this->assertCount(0, $result);

        //FILTER DISABLED
        $this->disableFilter();
        $result = $this->getRepository()
            ->findBy(['firstName' => LoadRequestData::FIRST_NAME_DELETED]);

        $this->assertCount(1, $result);

        //CHECK QUERIES
        $this->checkQueries();
    }

    public function testFindAllMethod()
    {
        //FILTER ENABLED
        $this->enableFilter();
        $result = $this->getRepository()
            ->findAll();

        $this->assertCount(8, $result);

        //FILTER DISABLED
        $this->disableFilter();
        $result = $this->getRepository()
        ->findAll();

        $this->assertCount(9, $result);

        //CHECK QUERIES
        $this->checkQueries();
    }

    public function testInQueryBuilder()
    {
        //FILTER ENABLED
        $this->enableFilter();
        $result = $this->getRepository()
            ->createQueryBuilder('r')
            ->select('r')
            ->join('r.account', 'a')
            ->where('r.firstName = :name')
            ->setParameter('name', LoadRequestData::FIRST_NAME_DELETED)
            ->getQuery()
            ->execute();

        $this->assertCount(0, $result);

        //FILTER DISABLED
        $this->disableFilter();
        $result = $this->getRepository()
            ->createQueryBuilder('r')
            ->select('r')
            ->join('r.account', 'a')
            ->where('r.firstName = :name')
            ->setParameter('name', LoadRequestData::FIRST_NAME_DELETED)
            ->getQuery()
            ->execute();

        $this->assertCount(1, $result);

        //CHECK QUERIES
        $this->checkQueries();
    }

    public function testInQueryBuilderJoinRelation()
    {
        //FILTER ENABLED
        $this->enableFilter();
        $result = $this->em->createQueryBuilder()
            ->select('a')
            ->from('OroB2BAccountBundle:Account', 'a')
            ->join('OroB2BRFPBundle:Request', 'r', 'WITH', 'a = r.account')
            ->where('r.firstName = :name')
            ->setParameter('name', 'John')
            ->getQuery()
            ->execute();

        $this->assertCount(0, $result);

        //FILTER DISABLED
        $this->disableFilter();
        $result = $this->em->createQueryBuilder()
            ->select('a')
            ->from('OroB2BAccountBundle:Account', 'a')
            ->join('OroB2BRFPBundle:Request', 'r', 'WITH', 'a = r.account')
            ->where('r.firstName = :name')
            ->setParameter('name', 'John')
            ->getQuery()
            ->execute();
        $this->assertCount(1, $result);

        //CHECK QUERIES
        $this->checkQueries();
    }

    protected function enableFilter()
    {
        $filters = $this->em->getFilters();
        /** @var SoftDeleteableFilter $filter */
        $filter = $filters->enable(SoftDeleteableFilter::FILTER_ID);
        $filter->setEm($this->em);
    }

    protected function disableFilter()
    {
        $filters = $this->em->getFilters();
        $filters->disable(SoftDeleteableFilter::FILTER_ID);
    }

    /**
     * @param string $query
     */
    protected function assertQueryModified($query)
    {
        $needle = $this->getQueryNeedleString();
        $this->assertContains($needle, $query);
    }

    /**
     * @param string $query
     */
    protected function assertQueryNotModified($query)
    {
        $needle = $this->getQueryNeedleString();
        $this->assertNotContains($needle, $query);
    }

    /**
     * @return string
     */
    protected function getQueryNeedleString()
    {
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();
        $metadata = $this->em->getClassMetadata('OroB2B\Bundle\RFPBundle\Entity\Request');

        $column = $this->em
            ->getConfiguration()
            ->getQuoteStrategy()
            ->getColumnName(SoftDeleteableInterface::FIELD_NAME, $metadata, $platform);

        return $platform->getIsNullExpression($column);
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->em->getRepository('OroB2BRFPBundle:Request');
    }

    protected function checkQueries()
    {
        $queries = $this->queryTracker->getExecutedQueries();
        $this->assertQueryModified($queries[0]);
        $this->assertQueryNotModified($queries[1]);
    }
}
