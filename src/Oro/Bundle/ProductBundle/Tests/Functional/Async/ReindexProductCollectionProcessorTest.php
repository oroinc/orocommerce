<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\ProductBundle\Async\ReindexProductCollectionProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * @dbIsolationPerTest
 */
class ReindexProductCollectionProcessorTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    private ReindexProductCollectionProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductCollectionData::class
        ]);
        $this->processor = self::getContainer()->get('oro_product.async.reindex_product_collection_processor');
    }

    /**
     * @dataProvider getProcessProvider
     */
    public function testProcess(bool $isFull, array $additionalProductRefs, int $expectedMessagesCount): void
    {
        $segment = $this->getReference(LoadProductCollectionData::SEGMENT);
        $website = self::getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $additionalProductIds = array_map(function (string $productRef) {
            return $this->getReference($productRef)->getId();
        }, $additionalProductRefs);

        $messageData = [
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => $segment->getId(),
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => [$website->getId()],
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => $isFull,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS => $additionalProductIds,
        ];

        $session = self::getContainer()->get('oro_message_queue.transport.connection')->createSession();
        $message = $this->createMessage($messageData);
        $result = $this->processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::ACK, $result);

        self::assertMessagesCount(AsyncIndexer::TOPIC_REINDEX, $expectedMessagesCount);
    }

    public function getProcessProvider(): array
    {
        return [
            'Full process' => [
                'isFull' => true,
                'additionalProductRefs' => [],
                'expectedMessagesCount' => 2,
            ],
            'Partial process' => [
                'isFull' => false,
                'additionalProductRefs' => [],
                'expectedMessagesCount' => 2,
            ],
            'Full process with additional products' => [
                'isFull' => true,
                'additionalProductRefs' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                ],
                'expectedMessagesCount' => 3,
            ],
            'Partial process with additional products' => [
                'isFull' => false,
                'additionalProductRefs' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                ],
                'expectedMessagesCount' => 3,
            ],
        ];
    }

    private function createMessage(array $body): MessageInterface
    {
        $message = new Message();
        $message->setBody($body);
        $message->setMessageId('some_message_id');

        return $message;
    }
}
