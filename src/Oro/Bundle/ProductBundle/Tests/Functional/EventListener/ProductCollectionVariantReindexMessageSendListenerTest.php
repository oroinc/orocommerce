<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentNode;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadContentVariantSegmentsWithRelationsData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadSegmentsWithRelationsData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadWebCatalogsData;
use Oro\Bundle\ProductBundle\Tests\Functional\Stub\ProductCollectionSegmentHelperStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * @dbIsolationPerTest
 */
class ProductCollectionVariantReindexMessageSendListenerTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadWebCatalogsData::class]);
    }

    public function testListenerWhenNewSegmentCreated()
    {
        $this->setWebCatalog();
        $segment = $this->createNewContentVariantWithSegment()[0];

        $rootJob = $this->getRootJob();
        $this->assertRootJobContainsDependentJob($rootJob);
        $firstChildJobId = $this->getFirstChildJobId($rootJob);
        $expectedMessages = [
            [
                'topic' => ReindexProductCollectionBySegmentTopic::NAME,
                'message' => [
                    'job_id' => $firstChildJobId,
                    'id' => $segment->getId(),
                    'website_ids' => [$this->getDefaultWebsite()->getId()],
                    'definition' => null,
                    'is_full' => false,
                    'additional_products' => [],
                ]
            ]
        ];
        $this->assertEquals(
            $expectedMessages,
            self::getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME)
        );
    }

    public function testListenerWhenSegmentRemoved()
    {
        $this->setWebCatalog();
        /**
         * @var Segment $segment
         * @var ContentVariantInterface $contentVariant
         */
        [$segment, $contentVariant] = $this->createNewContentVariantWithSegment();
        self::clearMessageCollector();

        $qb = self::getContainer()->get('doctrine')
            ->getRepository(Job::class)
            ->createQueryBuilder('job');

        $qb
            ->delete(Job::class)
            ->where('1=1');

        $qb->getQuery()->execute();

        self::assertMessagesEmpty(ReindexProductCollectionBySegmentTopic::NAME);

        $em = $this->getEntityManager();
        $em->remove($contentVariant);
        $this->setTestContentVariantMetadata(ContentVariant::class);
        $em->flush();

        $rootJob = $this->getRootJob();
        $this->assertRootJobContainsDependentJob($rootJob);
        $firstChildJobId = $this->getFirstChildJobId($rootJob);
        $expectedMessages = [
            [
                'topic' => ReindexProductCollectionBySegmentTopic::NAME,
                'message' => [
                    'job_id' => $firstChildJobId,
                    'id' => null,
                    'website_ids' => [$this->getDefaultWebsite()->getId()],
                    'definition' => $segment->getDefinition(),
                    'is_full' => true,
                    'additional_products' => [],
                ]
            ]
        ];

        $this->assertEquals(
            $expectedMessages,
            self::getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME)
        );
    }

    public function testListenerWhenNewSegmentCreatedAndWebCatalogIsNotActive()
    {
        $this->createNewContentVariantWithSegment();

        self::assertMessagesEmpty(ReindexProductCollectionBySegmentTopic::NAME);
    }

    public function testListenerWhenNewSegmentCreatedAndWebCatalogIsOff()
    {
        $this->setWebCatalog();

        /** @var ProductCollectionSegmentHelperStub $helper */
        $helper = self::getContainer()->get('oro_product.helper.product_collection_segment');
        $helper->setIsWebCatalogUsageProviderEnabled(false);

        self::assertMessagesEmpty(ReindexProductCollectionBySegmentTopic::NAME);
    }

    public function testListenerWhenSegmentUpdatedButDefinitionNotChanged()
    {
        $this->loadFixtures([LoadContentVariantSegmentsWithRelationsData::class]);
        $this->setWebCatalog();

        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentsWithRelationsData::FIRST_SEGMENT);
        $entityManager = $this->getEntityManager();

        $segment->setName('Other name');
        $entityManager->persist($segment);
        $entityManager->flush();

        self::assertMessagesEmpty(ReindexProductCollectionBySegmentTopic::NAME);
    }

    public function testListenerWhenSegmentUpdatedAndDefinitionChanged()
    {
        $this->loadFixtures([LoadContentVariantSegmentsWithRelationsData::class]);
        $this->setWebCatalog();
        $this->setTestContentVariantMetadata(TestContentVariant::class);

        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentsWithRelationsData::FIRST_SEGMENT);
        $entityManager = $this->getEntityManager();

        $segment->setDefinition(json_encode(['columns' => ['columnName' => 'newColumn']], JSON_THROW_ON_ERROR));

        $entityManager->persist($segment);
        $entityManager->flush();
        // Clears cache in general config manager.
        self::getConfigManager(null)->reload();

        $rootJob = $this->getRootJob();
        $this->assertRootJobContainsDependentJob($rootJob);
        $firstChildJobId = $this->getFirstChildJobId($rootJob);
        $expectedMessages = [
            [
                'topic' => ReindexProductCollectionBySegmentTopic::NAME,
                'message' => [
                    'job_id' => $firstChildJobId,
                    'id' => $segment->getId(),
                    'website_ids' => [$this->getDefaultWebsite()->getId()],
                    'definition' => null,
                    'is_full' => false,
                    'additional_products' => [],
                ]
            ]
        ];
        $this->assertEquals(
            $expectedMessages,
            self::getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME)
        );
    }

    private function createNewContentVariantWithSegment(): array
    {
        $this->setTestContentVariantMetadata(TestContentVariant::class);
        $entityManager = $this->getEntityManager();

        $segment = new Segment();
        $segment->setType($this->getSegmentType());
        $segment->setName('Collection');
        $segment->setEntity(Product::class);
        $segment->setDefinition(json_encode(['columns' => [], 'filters' =>[]], JSON_THROW_ON_ERROR));

        $contentVariant = new TestContentVariant();
        $contentVariant->setProductCollectionSegment($segment);

        $contentNode = new TestContentNode();
        $contentNode->setWebCatalog($this->getReference(LoadWebCatalogsData::FIRST_WEB_CATALOG));
        $contentVariant->setNode($contentNode);

        $entityManager->persist($contentNode);
        $entityManager->persist($contentVariant);
        $entityManager->flush();

        return [$segment, $contentVariant];
    }

    private function getSegmentType(): SegmentType
    {
        /** @var EntityRepository $repository */
        $repository = self::getContainer()->get('doctrine')
            ->getManagerForClass(SegmentType::class)
            ->getRepository(SegmentType::class);

        return $repository->find(SegmentType::TYPE_STATIC);
    }

    private function setTestContentVariantMetadata(string $name): void
    {
        $entityManager = $this->getEntityManager();
        $metadata = $entityManager->getClassMetadata(ContentVariantInterface::class);
        $metadata->name = $name;
    }

    private function setWebCatalog(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogsData::FIRST_WEB_CATALOG)->getId()
        );
        $configManager->flush();
    }

    private function getDefaultWebsite(): Website
    {
        return self::getContainer()->get('oro_website.manager')->getDefaultWebsite();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Segment::class);
    }

    private function getRootJob(): Job
    {
        $namePrefix = sprintf(
            '%s:%s',
            ReindexProductCollectionBySegmentTopic::NAME,
            'listener'
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
