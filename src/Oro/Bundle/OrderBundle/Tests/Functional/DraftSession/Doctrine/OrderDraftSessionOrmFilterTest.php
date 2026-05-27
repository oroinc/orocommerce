<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\DraftSession\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemDraftData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\QueryTracker;

final class OrderDraftSessionOrmFilterTest extends WebTestCase
{
    private QueryTracker $queryTracker;
    private EntityManagerInterface $em;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadOrderLineItemDraftData::class]);

        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->queryTracker = new QueryTracker($this->em);
        $this->queryTracker->start();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->queryTracker->stop();
        parent::tearDown();
    }

    public function testFind(): void
    {
        /** @var OrderLineItem $draft */
        $draft = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);
        $draftId = $draft->getId();
        $this->em->clear();

        // Filter enabled - should not find draft with draftSessionUuid
        $this->enableFilter();
        $queriesBeforeEnabled = count($this->queryTracker->getExecutedQueries());
        $result = $this->getRepository()->find($draftId);

        self::assertNull($result);
        $allQueries = $this->queryTracker->getExecutedQueries();
        $newQueries = array_slice($allQueries, $queriesBeforeEnabled);
        self::assertGreaterThanOrEqual(1, count($newQueries), 'Expected at least one query with filter enabled');
        $this->assertQueryModified($newQueries[0]);

        // Filter disabled - should find draft
        $this->em->clear();
        $this->disableFilter();
        $queriesBeforeDisabled = count($this->queryTracker->getExecutedQueries());
        $result = $this->getRepository()->find($draftId);

        self::assertNotNull($result);
        self::assertNotNull($result->getDraftSessionUuid());
        $allQueries = $this->queryTracker->getExecutedQueries();
        $newQueries = array_slice($allQueries, $queriesBeforeDisabled);
        self::assertGreaterThanOrEqual(1, count($newQueries), 'Expected at least one query with filter disabled');
        $mainQuery = $this->findMainQuery($newQueries);
        $this->assertQueryNotModified($mainQuery);
    }

    public function testFindByMethod(): void
    {
        $this->em->clear();

        // Filter enabled - should not find drafts
        $this->enableFilter();
        $queriesBeforeEnabled = count($this->queryTracker->getExecutedQueries());
        $qb = $this->getRepository()->createQueryBuilder('oli');
        $qb->where($qb->expr()->isNotNull('oli.draftSessionUuid'));
        $result = $qb->getQuery()->getResult();

        self::assertCount(0, $result);
        $allQueries = $this->queryTracker->getExecutedQueries();
        $newQueries = array_slice($allQueries, $queriesBeforeEnabled);
        self::assertGreaterThanOrEqual(1, count($newQueries), 'Expected at least one query with filter enabled');
        $this->assertQueryModified($newQueries[0]);

        // Filter disabled - should find drafts (we have 3 drafts in fixtures)
        $this->disableFilter();
        $this->em->clear();
        $queriesBeforeDisabled = count($this->queryTracker->getExecutedQueries());
        $qb = $this->getRepository()->createQueryBuilder('oli');
        $qb->where($qb->expr()->isNotNull('oli.draftSessionUuid'));
        $result = $qb->getQuery()->getResult();

        self::assertCount(2, $result);
        $allQueries = $this->queryTracker->getExecutedQueries();
        $newQueries = array_slice($allQueries, $queriesBeforeDisabled);
        self::assertGreaterThanOrEqual(1, count($newQueries), 'Expected at least one query with filter disabled');
        $this->assertQueryNotModified($newQueries[0]);
    }

    public function testFindAllMethod(): void
    {
        $this->em->clear();

        // Filter enabled - should exclude drafts (2 regular line items)
        $this->enableFilter();
        $queriesBeforeEnabled = count($this->queryTracker->getExecutedQueries());
        $resultWithFilter = $this->getRepository()->findAll();

        self::assertCount(2, $resultWithFilter);
        $allQueries = $this->queryTracker->getExecutedQueries();
        $newQueries = array_slice($allQueries, $queriesBeforeEnabled);
        self::assertGreaterThanOrEqual(1, count($newQueries), 'Expected at least one query with filter enabled');
        $this->assertQueryModified($newQueries[0]);

        // Filter disabled - should include drafts (2 regular + 3 drafts = 5 total)
        $this->disableFilter();
        $this->em->clear();
        $queriesBeforeDisabled = count($this->queryTracker->getExecutedQueries());
        $resultWithoutFilter = $this->getRepository()->findAll();

        self::assertCount(4, $resultWithoutFilter);
        $allQueries = $this->queryTracker->getExecutedQueries();
        $newQueries = array_slice($allQueries, $queriesBeforeDisabled);
        self::assertGreaterThanOrEqual(1, count($newQueries), 'Expected at least one query with filter disabled');
        $this->assertQueryNotModified($newQueries[0]);
    }

    private function enableFilter(): void
    {
        $filters = $this->em->getFilters();
        $filters->enable('order_draft');
    }

    private function disableFilter(): void
    {
        $filters = $this->em->getFilters();
        $filters->disable('order_draft');
    }

    private function assertQueryModified(string $query): void
    {
        $needle = $this->getQueryNeedleString();
        self::assertStringContainsString($needle, $query);
    }

    private function assertQueryNotModified(string $query): void
    {
        $needle = $this->getQueryNeedleString();
        self::assertStringNotContainsString($needle, $query);
    }

    private function getQueryNeedleString(): string
    {
        $metadata = $this->em->getClassMetadata(OrderLineItem::class);
        $columnName = $metadata->getColumnName('draftSessionUuid');

        // Return a pattern that will match with or without table alias prefix
        return $columnName . ' IS NULL';
    }

    private function findMainQuery(array $queries): string
    {
        $tableName = $this->em->getClassMetadata(OrderLineItem::class)->getTableName();

        foreach ($queries as $query) {
            if (str_contains($query, $tableName)) {
                return $query;
            }
        }

        return $queries[0];
    }

    private function getRepository(): EntityRepository
    {
        return $this->em->getRepository(OrderLineItem::class);
    }
}
