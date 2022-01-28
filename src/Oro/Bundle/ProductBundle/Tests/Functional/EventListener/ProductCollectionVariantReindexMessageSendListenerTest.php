<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ObjectManager;
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
use Oro\Component\MessageQueue\Client\Message;
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
        $this->initClient([], static::generateBasicAuthHeader());
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
                'message' => new Message([
                    'job_id' => $firstChildJobId,
                    'id' => $segment->getId(),
                    'website_ids' => [$this->getDefaultWebsite()->getId()],
                    'definition' => null,
                    'is_full' => false,
                    'additional_products' => [],
                ])
            ]
        ];
        $this->assertEquals(
            $expectedMessages,
            self::getMessageCollector()->getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME)
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
        $messageCollector = self::getMessageCollector();
        $messageCollector->clear();

        $qb = self::getContainer()->get('doctrine')
            ->getRepository(Job::class)
            ->createQueryBuilder('job');

        $qb
            ->delete('OroMessageQueueBundle:Job')
            ->where('1=1');

        $qb->getQuery()->execute();

        $this->assertEmpty($messageCollector->getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME));
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
                'message' => new Message([
                    'job_id' => $firstChildJobId,
                    'id' => null,
                    'website_ids' => [$this->getDefaultWebsite()->getId()],
                    'definition' => $segment->getDefinition(),
                    'is_full' => true,
                    'additional_products' => [],
                ])
            ]
        ];

        $this->assertEquals(
            $expectedMessages,
            $messageCollector->getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME)
        );
    }

    public function testListenerWhenNewSegmentCreatedAndWebCatalogIsNotActive()
    {
        $this->createNewContentVariantWithSegment();

        $this->assertEmpty(
            self::getMessageCollector()->getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME)
        );
    }

    public function testListenerWhenNewSegmentCreatedAndWebCatalogIsOff()
    {
        $this->setWebCatalog();

        /** @var ProductCollectionSegmentHelperStub $helper */
        $helper = self::getContainer()->get('oro_product.helper.product_collection_segment');
        $helper->setIsWebCatalogUsageProviderEnabled(false);

        $this->assertEmpty(
            self::getMessageCollector()->getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME)
        );
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

        $this->assertEmpty(
            self::getMessageCollector()->getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME)
        );
    }

    public function testListenerWhenSegmentUpdatedAndDefinitionChanged()
    {
        $this->loadFixtures([LoadContentVariantSegmentsWithRelationsData::class]);
        $this->setWebCatalog();
        $this->setTestContentVariantMetadata(TestContentVariant::class);

        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentsWithRelationsData::FIRST_SEGMENT);
        $entityManager = $this->getEntityManager();

        $segment->setDefinition(json_encode(['columns' => ['columnName' => 'newColumn']]));

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
                'message' => new Message([
                    'job_id' => $firstChildJobId,
                    'id' => $segment->getId(),
                    'website_ids' => [$this->getDefaultWebsite()->getId()],
                    'definition' => null,
                    'is_full' => false,
                    'additional_products' => [],
                ])
            ]
        ];
        $this->assertEquals(
            $expectedMessages,
            self::getMessageCollector()->getTopicSentMessages(ReindexProductCollectionBySegmentTopic::NAME)
        );
    }

    /**
     * @return array
     */
    private function createNewContentVariantWithSegment()
    {
        $this->setTestContentVariantMetadata(TestContentVariant::class);
        $entityManager = $this->getEntityManager();

        $segment = new Segment();
        $segment->setType($this->getSegmentType());
        $segment->setName('Collection');
        $segment->setEntity(Product::class);
        $segment->setDefinition(json_encode(['columns' => [], 'filters' =>[]]));

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

    /**
     * @return SegmentType
     */
    private function getSegmentType()
    {
        /** @var EntityRepository $repository */
        $repository = self::getContainer()->get('doctrine')
            ->getManagerForClass(SegmentType::class)
            ->getRepository(SegmentType::class);

        return $repository->find(SegmentType::TYPE_STATIC);
    }

    private function setTestContentVariantMetadata(string $name)
    {
        $entityManager = $this->getEntityManager();
        $metadata = $entityManager->getClassMetadata(ContentVariantInterface::class);
        $metadata->name = $name;
    }

    private function setWebCatalog()
    {
        $configManager = self::getConfigManager('global');
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogsData::FIRST_WEB_CATALOG)->getId()
        );
        $configManager->flush();
    }

    /**
     * @return Website
     */
    private function getDefaultWebsite()
    {
        $websiteManager = self::getContainer()->get('oro_website.manager');

        return $websiteManager->getDefaultWebsite();
    }

    /**
     * @return ObjectManager|null|object
     */
    private function getEntityManager()
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Segment::class);
    }

    protected function getRootJob(): Job
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

    protected function assertRootJobContainsDependentJob(Job $rootJob): void
    {
        $data = $rootJob->getData();
        self::assertArrayHasKey('dependentJobs', $data);

        self::assertSame(
            $data['dependentJobs'],
            [
                [
                    'topic' => 'oro_product.reindex_request_item_products_by_related_job',
                    'message' => [
                        'relatedJobId' => $rootJob->getId()
                    ],
                    'priority' => null
                ]
            ]
        );
    }

    protected function getFirstChildJobId(Job $rootJob): int
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
