<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Command;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\CronBundle\Entity\Repository\ScheduleRepository;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topics;
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

    /**
     * @var string
     */
    private $prevVariantClass;

    protected function setUp()
    {
        $this->initClient();

        $metadata = $this->getContentVariantMetadata();
        $this->prevVariantClass = $metadata->getName();
        $metadata->name = TestContentVariant::class;

        $this->loadFixtures([LoadContentVariantSegmentsWithRelationsData::class]);
    }

    protected function tearDown()
    {
        $metadata = $this->getContentVariantMetadata();
        $metadata->name = $this->prevVariantClass;
    }

    public function testCommandWhenWebCatalogIsUsed()
    {
        $websiteManager = self::getContainer()->get('oro_website.manager');
        $configManager = self::getContainer()->get('oro_config.manager');

        $defaultWebsite = $websiteManager->getDefaultWebsite();
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogsData::FIRST_WEB_CATALOG)->getId(),
            $defaultWebsite
        );

        $configManager->flush();

        self::runCommand(ProductCollectionsIndexCronCommand::NAME, []);

        $expectedMessage = [
            [
                'topic' => Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                'message' => [
                    'id' => $this->getReference(LoadSegmentsWithRelationsData::FIRST_SEGMENT)->getId(),
                    'website_ids' => [$defaultWebsite->getId()],
                    'definition' => null,
                    'is_full' => true,
                    'additional_products' => [],
                ]
            ],
            [
                'topic' => Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                'message' => [
                    'id' => $this->getReference(LoadSegmentsWithRelationsData::SECOND_SEGMENT)->getId(),
                    'website_ids' => [$defaultWebsite->getId()],
                    'definition' => null,
                    'is_full' => true,
                    'additional_products' => [],
                ]
            ],
            [
                'topic' => Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                'message' => [
                    'id' => $this->getReference(LoadSegmentsWithRelationsData::WITH_CRITERIA_SEGMENT)->getId(),
                    'website_ids' => [$defaultWebsite->getId()],
                    'definition' => null,
                    'is_full' => true,
                    'additional_products' => [],
                ]
            ],
        ];

        $this->assertEquals(
            $expectedMessage,
            self::getMessageCollector()->getTopicSentMessages(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT)
        );
    }

    public function testCommandWhenWebCatalogIsNotUsed()
    {
        self::runCommand(ProductCollectionsIndexCronCommand::NAME, []);

        $traces = self::getMessageCollector()->getTopicSentMessages(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT);

        $this->assertCount(0, $traces);
    }

    public function testGetDefaultDefinitions()
    {
        /** @var ScheduleRepository $repo */
        $repo = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityRepositoryForClass(Schedule::class);
        /** @var Schedule $commandSchedule */
        $commandSchedule = $repo->findOneBy(['command' => ProductCollectionsIndexCronCommand::NAME]);
        $this->assertNotEmpty($commandSchedule);
        $this->assertSame(Configuration::DEFAULT_CRON_SCHEDULE, $commandSchedule->getDefinition());

        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set(ProductCollectionsScheduleConfigurationListener::CONFIG_FIELD, '0 0 0 0 *');
        $configManager->flush();
        self::runCommand('oro:cron:definitions:load', []);

        $commandSchedule = $repo->findOneBy(['command' => ProductCollectionsIndexCronCommand::NAME]);
        $this->assertSame('0 0 0 0 *', $commandSchedule->getDefinition());
    }

    /**
     * @return ClassMetadata
     */
    protected function getContentVariantMetadata(): ClassMetadata
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Segment::class);
        return $em->getClassMetadata(ContentVariantInterface::class);
    }
}
