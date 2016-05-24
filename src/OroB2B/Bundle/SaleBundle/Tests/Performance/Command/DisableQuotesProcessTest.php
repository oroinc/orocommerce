<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Performance\Command;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Performance\PerformanceMeasureTrait;

use OroB2B\Bundle\SaleBundle\Tests\Performance\DataFixtures\LoadQuoteDataForPerformance;

/**
 * @dbIsolation
 */
class DisableQuotesProcessTest extends WebTestCase
{
    use PerformanceMeasureTrait;

    const PROCESS_DEFINITION = 'expire_quotes';

    const MAX_EXECUTION_TIME = 120;

    /** @var EntityManagerInterface */
    protected $quoteEm;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            ['OroB2B\Bundle\SaleBundle\Tests\Performance\DataFixtures\LoadQuoteDataForPerformance']
        );
        $this->doctrineHelper = $this->client->getContainer()->get('oro_entity.doctrine_helper');
        $this->quoteEm = $this->doctrineHelper->getEntityManager('OroB2BSaleBundle:Quote');
    }

    public function testDisableQuotesProcessPerformance()
    {
        // Get new quote number after fixtures
        $totalQuotes = $this->getQuoteCount();
        $quotesToExpire = $this->getQuoteCount(true);

        // Assert that quotes were imported
        $this->assertEquals(LoadQuoteDataForPerformance::NUMBER_OF_QUOTE_GROUPS * 7, $totalQuotes);
        $this->assertEquals(LoadQuoteDataForPerformance::QUOTES_TO_EXPIRE, $quotesToExpire);

        $expireQuotesTrigger = $this->doctrineHelper->getEntityRepository('OroWorkflowBundle:ProcessTrigger')
            ->findOneBy(
                ['definition' => static::PROCESS_DEFINITION]
            );

        self::startMeasurement(__METHOD__);
        $this->runCommand('oro:process:handle-trigger', [
            '--name=' . self::PROCESS_DEFINITION,
            '--id=' . $expireQuotesTrigger->getId()
        ]);
        $duration = self::stopMeasurement(__METHOD__) / 1000;

        $quotesRemainingToExpire = $this->getQuoteCount(true);

        $this->assertLessThan(self::MAX_EXECUTION_TIME, $duration);
        $this->assertEquals(0, $quotesRemainingToExpire);
    }

    /**
     * @param bool $onlyNotExpired
     * @return int
     */
    protected function getQuoteCount($onlyNotExpired = false)
    {
        $qb = $this->quoteEm->createQueryBuilder()
            ->select('COUNT(q)')
            ->from('OroB2BSaleBundle:Quote', 'q');

        if ($onlyNotExpired) {
            $qb->where('q.expired = FALSE')
                ->andWhere('q.validUntil <= :date')
                ->setParameter('date', new \DateTime('now', new \DateTimeZone("UTC")));
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }
}
