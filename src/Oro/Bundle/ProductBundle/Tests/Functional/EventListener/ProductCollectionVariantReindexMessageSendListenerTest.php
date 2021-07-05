<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentNode;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadContentVariantSegmentsWithRelationsData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadSegmentsWithRelationsData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadWebCatalogsData;
use Oro\Bundle\ProductBundle\Tests\Functional\Stub\ProductCollectionSegmentHelperStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
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
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([LoadWebCatalogsData::class]);
    }

    public function testListenerWhenNewSegmentCreated()
    {
        $this->setWebCatalog();
        $segment = $this->createNewContentVariantWithSegment()[0];

        $expectedMessages = [
            [
                'topic' => Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                'message' => [
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
            self::getMessageCollector()->getTopicSentMessages(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT)
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

        $this->assertEmpty($messageCollector->getTopicSentMessages(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT));
        $em = $this->getEntityManager();
        $em->remove($contentVariant);
        $em->flush();

        $expectedMessages = [
            [
                'topic' => Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                'message' => [
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
            $messageCollector->getTopicSentMessages(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT)
        );
    }

    public function testListenerWhenNewSegmentCreatedAndWebCatalogIsNotActive()
    {
        $this->createNewContentVariantWithSegment();

        $this->assertEmpty(
            self::getMessageCollector()->getTopicSentMessages(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT)
        );
    }

    public function testListenerWhenNewSegmentCreatedAndWebCatalogIsOff()
    {
        $this->setWebCatalog();

        /** @var ProductCollectionSegmentHelperStub $helper */
        $helper = self::getContainer()->get('oro_product.helper.product_collection_segment');
        $helper->setIsWebCatalogUsageProviderEnabled(false);

        $this->assertEmpty(
            self::getMessageCollector()->getTopicSentMessages(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT)
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
            self::getMessageCollector()->getTopicSentMessages(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT)
        );
    }

    public function testListenerWhenSegmentUpdatedAndDefinitionChanged()
    {
        $this->loadFixtures([LoadContentVariantSegmentsWithRelationsData::class]);
        $this->setWebCatalog();
        $this->setTestContentVariantMetadata();

        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentsWithRelationsData::FIRST_SEGMENT);
        $entityManager = $this->getEntityManager();

        $segment->setDefinition(json_encode(['columns' => ['columnName' => 'newColumn']]));

        $entityManager->persist($segment);
        $entityManager->flush();
        // Clears cache in general config manager.
        self::getConfigManager(null)->reload();

        $expectedMessages = [
            [
                'topic' => Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                'message' => [
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
            self::getMessageCollector()->getTopicSentMessages(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT)
        );
    }

    /**
     * @return array
     */
    private function createNewContentVariantWithSegment()
    {
        $this->setTestContentVariantMetadata();
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

    private function setTestContentVariantMetadata()
    {
        $entityManager = $this->getEntityManager();
        $metadata = $entityManager->getClassMetadata(ContentVariantInterface::class);
        $metadata->name = TestContentVariant::class;
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
}
