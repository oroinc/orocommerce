<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Async;

use Monolog\Handler\TestHandler;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\ReindexRequestItemProductsByRelatedJobProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductWebsiteReindexRequestItems;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Yaml\Yaml;

/**
 * @dbIsolationPerTest
 */
class ReindexRequestItemProductsByRelatedJobProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use JobsAwareTestTrait;

    private ReindexRequestItemProductsByRelatedJobProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->setUpMessageCollector();
        $this->loadFixtures([
            LoadProductWebsiteReindexRequestItems::class,
        ]);
        $this->processor = self::getContainer()->get(
            'oro_product.async.reindex_request_item_products_by_related_job_processor'
        );
    }

    public function testProcessNonExistingRelatedJobId()
    {
        $session = $this->getConnection()->createSession();
        $message = $this->createMessage(
            [
                'relatedJobId' => 999,
                'indexationFieldsGroups' => null,
            ]
        );

        /** @var Logger $logger */
        $logger = self::getContainer()->get('logger');
        $logger->pushHandler(new TestHandler());
        $result = $this->processor->process($message, $session);
        self::assertEquals(
            'oro.message_queue.consumption.ack',
            $result
        );

        self::assertMessagesEmpty(WebsiteSearchReindexTopic::getName());
        self::assertEmpty($logger->getLogs());
    }

    /**
     * @dataProvider testProcessProvider
     *
     * @return void
     */
    public function testProcess(int $relatedJobId, array $fieldGroups = null): void
    {
        $session = $this->getConnection()->createSession();
        $message = $this->createMessage(
            [
                'relatedJobId' => $relatedJobId,
                'indexationFieldsGroups' => $fieldGroups,
            ]
        );

        $this->createRootJobMyMessage($message);

        /** @var Logger $logger */
        $logger = self::getContainer()->get('logger');
        $logger->pushHandler(new TestHandler());
        $this->processor->setBatchSize(3);
        $result = $this->processor->process($message, $session);
        self::assertEquals(
            'oro.message_queue.consumption.ack',
            $result
        );
        self::assertEmpty($logger->getLogs());

        $messageBodies = self::getSentMessagesByTopic(WebsiteSearchReindexTopic::getName());
        self::assertNotEmpty($messageBodies);
        $valuableDataFromMessageBodies = \array_map(
            [$this, 'getValuableDataFromMessageBody'],
            $messageBodies
        );

        $expectedData = $this->getExpectedResultsFor($relatedJobId, $fieldGroups);
        self::assertEquals($expectedData, $valuableDataFromMessageBodies);

        $this->assertNoRecordsForRelatedJobId($relatedJobId);
    }

    public function testProcessProvider(): array
    {
        return [
            'Test process products in different websites' => [
                'relatedJobId' => LoadProductWebsiteReindexRequestItems::JOB_ID_W_PRODUCT_IN_DIFFERENT_WEBSITES,
                'indexationFieldsGroups' => null,
            ],
            'Test process products in different websites group main' => [
                'relatedJobId' => LoadProductWebsiteReindexRequestItems::JOB_ID_W_PRODUCT_IN_DIFFERENT_WEBSITES,
                'indexationFieldsGroups' => ['main'],
            ],
            'Test process products in same websites' => [
                'relatedJobId' => LoadProductWebsiteReindexRequestItems::JOB_ID_W_PRODUCT_IN_SAME_WEBSITES,
                'indexationFieldsGroups' => null,
            ],
        ];
    }

    private function getExpectedResultsFor(string $relatedJobId, array $fieldsGroups = null): array
    {
        $expectedResults = Yaml::parse(
            file_get_contents(
                __DIR__ .
                '/../DataFixtures/data/ReindexRequestItemProductsByRelatedJobProcessorTest/expected_results.yml'
            )
        );

        $data = $expectedResults['data'][$relatedJobId];
        if ($fieldsGroups) {
            foreach ($data as &$row) {
                $row['context']['fieldGroups'] = $fieldsGroups;
            }
        }

        return $data;
    }

    private function assertNoRecordsForRelatedJobId(int $relatedJobId): void
    {
        $websiteIds = self::getContainer()
            ->get('oro_product.driver.product_website_reindex_request_dbal_driver')
            ->getWebsiteIdsByRelatedJobId($relatedJobId);

        self::assertEmpty($websiteIds);
    }

    private function getValuableDataFromMessageBody(array $messageBody): array
    {
        self::assertArrayHasKey('jobId', $messageBody);
        unset($messageBody['jobId']);

        return $messageBody;
    }

    private function getConnection(): ConnectionInterface
    {
        return self::getContainer()->get('oro_message_queue.transport.connection');
    }

    private function createMessage(array $body): MessageInterface
    {
        $message = new Message();
        $message->setBody($body);
        $message->setMessageId('some_message_id');
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => ReindexRequestItemProductsByRelatedJobIdTopic::getName()
        ]);

        return $message;
    }
}
