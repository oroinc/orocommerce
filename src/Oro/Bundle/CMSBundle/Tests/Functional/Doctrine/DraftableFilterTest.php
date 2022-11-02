<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadDraftPageData;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\QueryTracker;

class DraftableFilterTest extends WebTestCase
{
    private QueryTracker $queryTracker;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadDraftPageData::class]);

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->queryTracker = new QueryTracker($this->em);
        $this->queryTracker->start();
    }

    protected function tearDown(): void
    {
        $this->queryTracker->stop();
        parent::tearDown();
    }

    public function testFind(): void
    {
        /** @var Page $page */
        $page = $this->getReferenceRepository()->getReference(LoadDraftPageData::BASIC_PAGE_1_DRAFT_1);
        $this->em->detach($page);

        // Filter enabled
        $this->enableFilter();
        $result = $this->getRepository()
            ->find($page->getId());

        $this->assertNull($result);

        // Filter disabled
        $this->disableFilter();
        $result = $this->getRepository()
            ->find($page->getId());

        $this->assertNotNull($result);
        $this->assertEquals(LoadDraftPageData::BASIC_PAGE_1_DRAFT_1, $result->getContent());

        // Check queries
        $this->checkQueries();
    }

    public function testFindByMethod(): void
    {
        // Filter enabled
        $this->enableFilter();
        $result = $this->getRepository()
            ->findBy(['content' => LoadDraftPageData::BASIC_PAGE_1_DRAFT_1]);

        $this->assertCount(0, $result);

        // Filter disabled
        $this->disableFilter();
        $result = $this->getRepository()
            ->findBy(['content' => LoadDraftPageData::BASIC_PAGE_1_DRAFT_1]);

        $this->assertCount(1, $result);

        // Check queries
        $this->checkQueries();
    }

    public function testFindAllMethod(): void
    {
        // Filter enabled
        $this->enableFilter();
        $result = $this->getRepository()
            ->findAll();

        $this->assertCount(2, $result);

        // Filter disabled
        $this->disableFilter();
        $result = $this->getRepository()
            ->findAll();

        $this->assertCount(4, $result);

        // Check queries
        $this->checkQueries();
    }

    public function testInQueryBuilderJoinRelation(): void
    {
        // Filter enabled
        $this->enableFilter();
        $result = $this->getOrganizationWithDraftPage();

        $this->assertCount(0, $result);

        // Filter disabled
        $this->disableFilter();
        $result = $this->getOrganizationWithDraftPage();

        $this->assertCount(1, $result);

        // Check queries
        $this->checkQueries();
    }

    private function enableFilter(): void
    {
        $filters = $this->em->getFilters();
        $filters->enable(DraftableFilter::FILTER_ID);
    }

    private function disableFilter(): void
    {
        $filters = $this->em->getFilters();
        $filters->disable(DraftableFilter::FILTER_ID);
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
        $platform = $this->em->getConnection()->getDatabasePlatform();
        $metadata = $this->em->getClassMetadata(Page::class);

        return $platform->getIsNullExpression($metadata->getColumnName('draftUuid'));
    }

    private function getRepository(): EntityRepository
    {
        return $this->em->getRepository(Page::class);
    }

    private function checkQueries(): void
    {
        $queries = $this->queryTracker->getExecutedQueries();
        $this->assertQueryModified($queries[0]);
        $this->assertQueryNotModified($queries[1]);
    }

    private function getOrganizationWithDraftPage(): array
    {
        return $this->em->createQueryBuilder()
            ->select('o')
            ->from(Organization::class, 'o')
            ->join(Page::class, 'p', 'WITH', 'o = p.organization')
            ->where('p.content = :content')
            ->setParameter('content', LoadDraftPageData::BASIC_PAGE_1_DRAFT_1)
            ->getQuery()
            ->execute();
    }
}
