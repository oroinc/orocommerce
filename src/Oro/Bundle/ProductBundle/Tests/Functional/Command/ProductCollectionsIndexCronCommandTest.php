<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Command\ProductCollectionsIndexCronCommand;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionsScheduleConfigurationListener;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadContentVariantSegmentsWithRelationsData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadSegmentsWithRelationsData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadWebCatalogsData;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * @dbIsolationPerTest
 */
class ProductCollectionsIndexCronCommandTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;

    private string $prevVariantClass;

    protected function setUp(): void
    {
        $this->initClient();

        $metadata = $this->getContentVariantMetadata();
        $this->prevVariantClass = $metadata->getName();
        $metadata->name = TestContentVariant::class;

        $this->loadFixtures([LoadContentVariantSegmentsWithRelationsData::class]);
    }

    protected function tearDown(): void
    {
        $metadata = $this->getContentVariantMetadata();
        $metadata->name = $this->prevVariantClass;
    }

    /**
     * @dataProvider partialConfigDataProvider
     */
    public function testCommandWhenWebCatalogIsUsed(bool $isPartialConfig)
    {
        $configManager = self::getConfigManager();
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogsData::FIRST_WEB_CATALOG)->getId()
        );
        $configManager->set(
            'oro_product.product_collections_indexation_partial',
            $isPartialConfig
        );
        $configManager->flush();

        self::runCommand(ProductCollectionsIndexCronCommand::getDefaultName(), []);

        $isFullReindex = false === $isPartialConfig;
        $rootJob = $this->getRootJob($isFullReindex);
        $this->assertRootJobContainsDependentJob($rootJob);
        $firstChildJobId = $this->getFirstChildJobId($rootJob);
        $websiteManager = self::getContainer()->get('oro_website.manager');
        $defaultWebsite = $websiteManager->getDefaultWebsite();
        $expectedMessage = [
            [
                'topic' => ReindexProductCollectionBySegmentTopic::NAME,
                'message' => [
                    'job_id' => $firstChildJobId,
                    'id' => $this->getReference(LoadSegmentsWithRelationsData::FIRST_SEGMENT)->getId(),
                    'website_ids' => [$defaultWebsite->getId()],
                    'definition' => null,
                    'is_full' => !$isPartialConfig,
                    'additional_products' => [],
                ]
            ],
            [
                'topic' => ReindexProductCollectionBySegmentTopic::NAME,
                'message' => [
                    'job_id' => $firstChildJobId + 1,
                    'id' => $this->getReference(LoadSegmentsWithRelationsData::SECOND_SEGMENT)->getId(),
                    'website_ids' => [$defaultWebsite->getId()],
                    'definition' => null,
                    'is_full' => !$isPartialConfig,
                    'additional_products' => [],
                ]
            ],
            [
                'topic' => ReindexProductCollectionBySegmentTopic::NAME,
                'message' => [
                    'job_id' => $firstChildJobId + 2,
                    'id' => $this->getReference(LoadSegmentsWithRelationsData::WITH_CRITERIA_SEGMENT)->getId(),
                    'website_ids' => [$defaultWebsite->getId()],
                    'definition' => null,
                    'is_full' => !$isPartialConfig,
                    'additional_products' => [],
                ]
            ],
        ];

        self::assertEquals(
            $expectedMessage,
            self::getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME)
        );
    }

    public function partialConfigDataProvider(): array
    {
        return [
            'partial' => [true],
            'full' => [false]
        ];
    }

    public function testCommandWhenWebCatalogIsUsedPartialOptionPassed()
    {
        $configManager = self::getConfigManager();
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogsData::FIRST_WEB_CATALOG)->getId()
        );
        $configManager->set(
            'oro_product.product_collections_indexation_partial',
            false
        );
        $configManager->flush();

        self::runCommand(ProductCollectionsIndexCronCommand::getDefaultName(), ['--partial-reindex']);

        $rootJob = $this->getRootJob(false);
        $this->assertRootJobContainsDependentJob($rootJob);
        $firstChildJobId = $this->getFirstChildJobId($rootJob);
        $websiteManager = self::getContainer()->get('oro_website.manager');
        $defaultWebsite = $websiteManager->getDefaultWebsite();
        $expectedMessage = [
            [
                'topic' => ReindexProductCollectionBySegmentTopic::NAME,
                'message' => [
                    'job_id' => $firstChildJobId,
                    'id' => $this->getReference(LoadSegmentsWithRelationsData::FIRST_SEGMENT)->getId(),
                    'website_ids' => [$defaultWebsite->getId()],
                    'definition' => null,
                    'is_full' => false,
                    'additional_products' => [],
                ]
            ],
            [
                'topic' => ReindexProductCollectionBySegmentTopic::NAME,
                'message' => [
                    'job_id' => $firstChildJobId + 1,
                    'id' => $this->getReference(LoadSegmentsWithRelationsData::SECOND_SEGMENT)->getId(),
                    'website_ids' => [$defaultWebsite->getId()],
                    'definition' => null,
                    'is_full' => false,
                    'additional_products' => [],
                ]
            ],
            [
                'topic' => ReindexProductCollectionBySegmentTopic::NAME,
                'message' => [
                    'job_id' => $firstChildJobId + 2,
                    'id' => $this->getReference(LoadSegmentsWithRelationsData::WITH_CRITERIA_SEGMENT)->getId(),
                    'website_ids' => [$defaultWebsite->getId()],
                    'definition' => null,
                    'is_full' => false,
                    'additional_products' => [],
                ]
            ],
        ];

        self::assertEquals($expectedMessage, self::getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME));
    }

    /**
     * @dataProvider partialConfigDataWithCommandResponseDataProvider
     */
    public function testTryRunCronCommandBeforePreviousJobComplete(bool $isPartial, string $expectedResponse)
    {
        $this->testCommandWhenWebCatalogIsUsed($isPartial);

        self::getMessageCollector()->clear();

        $response = self::runCommand(ProductCollectionsIndexCronCommand::getDefaultName(), []);
        self::assertEquals($expectedResponse, $response);
        self::assertMessagesEmpty(ReindexProductCollectionBySegmentTopic::NAME);
    }

    public function partialConfigDataWithCommandResponseDataProvider(): array
    {
        return [
            'partial' => [
                'isPartial' => true,
                'expectedResponse' =>
                    "Can't start the process because the same job on partial re-indexation is in progress."
            ],
            'full' => [
                'isPartial' => false,
                'expectedResponse' =>
                    "Can't start the process because the same job on full re-indexation is in progress."
            ]
        ];
    }

    public function testCommandWhenWebCatalogIsNotUsed()
    {
        self::runCommand(ProductCollectionsIndexCronCommand::getDefaultName(), []);

        self::assertMessagesEmpty(ReindexProductCollectionBySegmentTopic::NAME);
    }

    public function testGetDefaultDefinitions()
    {
        /** @var EntityRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(Schedule::class);
        /** @var Schedule $commandSchedule */
        $commandSchedule = $repo->findOneBy(['command' => ProductCollectionsIndexCronCommand::getDefaultName()]);
        self::assertNotEmpty($commandSchedule);
        self::assertSame(Configuration::DEFAULT_CRON_SCHEDULE, $commandSchedule->getDefinition());

        $configManager = self::getConfigManager();
        $configManager->set(ProductCollectionsScheduleConfigurationListener::CONFIG_FIELD, '0 0 0 0 *');
        $configManager->flush();
        self::runCommand('oro:cron:definitions:load', []);

        $commandSchedule = $repo->findOneBy(['command' => ProductCollectionsIndexCronCommand::getDefaultName()]);
        self::assertSame('0 0 0 0 *', $commandSchedule->getDefinition());
    }

    private function getContentVariantMetadata(): ClassMetadata
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass(Segment::class)
            ->getClassMetadata(ContentVariantInterface::class);
    }

    private function getRootJob(bool $isFull): Job
    {
        $namePrefix = sprintf(
            '%s:%s:%s',
            ReindexProductCollectionBySegmentTopic::NAME,
            'cron',
            $isFull ? 'full' : 'partial'
        );

        $qb = self::getContainer()->get('doctrine')
            ->getRepository(Job::class)
            ->createQueryBuilder('job');

        $qb
            ->select('job')
            ->where(
                $qb->expr()->like('job.name', ':namePrefix'),
                $qb->expr()->isNull('job.rootJob')
            )
            ->setParameter('namePrefix', $namePrefix . '%')
            ->setMaxResults(1)
            ->setFirstResult(0);

        $rootJob = $qb->getQuery()->getOneOrNullResult();
        self::assertNotNull($rootJob);

        return $rootJob;
    }

    private function assertRootJobContainsDependentJob(Job $rootJob): void
    {
        $data = $rootJob->getData();
        self::assertArrayHasKey('dependentJobs', $data);

        self::assertSame(
            $data['dependentJobs'],
            [
                [
                    'topic' => 'oro_product.reindex_request_item_products_by_related_job',
                    'message' => [
                        'relatedJobId' => $rootJob->getId(),
                        'indexationFieldsGroups' => ['main', 'collection_sort_order']
                    ],
                    'priority' => null
                ]
            ]
        );
    }

    private function getFirstChildJobId(Job $rootJob): int
    {
        $qb = self::getContainer()->get('doctrine')
            ->getRepository(Job::class)
            ->createQueryBuilder('job');
        $qb
            ->select('job.id')
            ->where(
                $qb->expr()->eq('job.rootJob', ':rootJob'),
                $qb->expr()->eq('job.status', ':jobStatus'),
            )
            ->setParameter('rootJob', $rootJob)
            ->setParameter('jobStatus', Job::STATUS_NEW)
            ->orderBy('job.id', 'ASC')
            ->setMaxResults(1)
            ->setFirstResult(0);

        $firstChildJobId = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_SCALAR_COLUMN);
        self::assertNotNull($firstChildJobId);

        return $firstChildJobId;
    }
}
