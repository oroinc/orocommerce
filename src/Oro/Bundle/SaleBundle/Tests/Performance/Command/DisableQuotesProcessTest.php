<?php

namespace Oro\Bundle\SaleBundle\Tests\Performance\Command;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SaleBundle\Tests\Performance\DataFixtures\LoadQuoteDataForPerformance;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Component\Testing\Performance\PerformanceMeasureTrait;

class DisableQuotesProcessTest extends WebTestCase
{
    use PerformanceMeasureTrait;

    private const PROCESS_DEFINITION = 'expire_quotes';
    private const MAX_EXECUTION_TIME = 120;

    private EntityManagerInterface $quoteEm;
    private DoctrineHelper $doctrineHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadQuoteDataForPerformance::class]);
        $this->doctrineHelper = $this->client->getContainer()->get('oro_entity.doctrine_helper');
        $this->quoteEm = $this->doctrineHelper->getEntityManager(Quote::class);
    }

    public function testDisableQuotesProcessPerformance()
    {
        // Get new quote number after fixtures
        $totalQuotes = $this->getQuoteCount();
        $quotesToExpire = $this->getQuoteCount(true);

        // Assert that quotes were imported
        $this->assertEquals(
            LoadQuoteDataForPerformance::NUMBER_OF_QUOTE_GROUPS * count(LoadQuoteData::$items),
            $totalQuotes
        );
        $this->assertEquals(LoadQuoteDataForPerformance::QUOTES_TO_EXPIRE, $quotesToExpire);

        $expireQuotesTrigger = $this->doctrineHelper->getEntityRepository(ProcessTrigger::class)
            ->findOneBy(['definition' => self::PROCESS_DEFINITION]);

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

    private function getQuoteCount(bool $onlyNotExpired = false): int
    {
        $qb = $this->quoteEm->createQueryBuilder()
            ->select('COUNT(q)')
            ->from(Quote::class, 'q');

        if ($onlyNotExpired) {
            $qb->where('q.expired = FALSE')
                ->andWhere('q.validUntil <= :date')
                ->setParameter('date', new \DateTime('now', new \DateTimeZone('UTC')), Types::DATETIME_MUTABLE);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
