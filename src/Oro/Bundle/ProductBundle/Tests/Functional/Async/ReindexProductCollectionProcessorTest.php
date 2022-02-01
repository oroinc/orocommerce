<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Async;

use Monolog\Handler\TestHandler;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\ProductBundle\Async\ReindexProductCollectionProcessor;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * @dbIsolationPerTest
 */
class ReindexProductCollectionProcessorTest extends WebTestCase
{
    use JobsAwareTestTrait;

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
     * @dataProvider testProcessProvider
     *
     * @param bool $isFull
     * @param array $expectedProductRefs
     * @param array $additionalProductRefs
     * @return void
     */
    public function testProcess(bool $isFull, array $expectedProductRefs, array $additionalProductRefs): void
    {
        /** @var Logger $logger */
        $logger = self::getContainer()->get('logger');
        $logger->pushHandler(new TestHandler());

        $childJob = $this->createDelayedJob();
        $segment = $this->getReference(LoadProductCollectionData::SEGMENT);
        $website = self::getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $additionalProductIds = array_map(function (string $productRef) {
            return $this->getReference($productRef)->getId();
        }, $additionalProductRefs);

        $messageData = self::getContainer()
            ->get('oro_product.model.segment_message_factory')
            ->createMessage(
                $childJob->getId(),
                [$website->getId()],
                $segment,
                isFull: $isFull,
                additionalProducts: $additionalProductIds
            );

        $session = $this->getConnection()->createSession();
        $message = $this->createMessage($messageData);
        $result = $this->processor->process($message, $session);
        self::assertEquals(
            'oro.message_queue.consumption.ack',
            $result
        );
        self::assertEmpty($logger->getLogs());

        $connection = self::getContainer()->get('doctrine')->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->select('*')
            ->from('oro_prod_webs_reindex_req_item', 'req_item')
            ->orderBy('req_item.product_id', 'ASC');

        $result = $connection->fetchAllAssociative($qb->getSQL());
        $expectedResult = array_map(function (string $productRef) use ($childJob, $website) {
            return [
                'related_job_id' => $childJob->getRootJob()->getId(),
                'website_id' => $website->getId(),
                'product_id' => $this->getReference($productRef)->getId()
            ];
        }, $expectedProductRefs);

        self::assertEquals($expectedResult, $result);
    }

    public function testProcessProvider(): array
    {
        return [
            'Full process' => [
                'isFull' => true,
                'expectedProductRefs' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                ],
                'additionalProductRefs' => []
            ],
            'Partial process' => [
                'isFull' => false,
                'expectedProductRefs' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                ],
                'additionalProductRefs' => []
            ],
            'Full process with additional products' => [
                'isFull' => true,
                'expectedProductRefs' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                ],
                'additionalProductRefs' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                ]
            ],
            'Partial process with additional products' => [
                'isFull' => false,
                'expectedProductRefs' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                ],
                'additionalProductRefs' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                ]
            ],
        ];
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

        return $message;
    }
}
