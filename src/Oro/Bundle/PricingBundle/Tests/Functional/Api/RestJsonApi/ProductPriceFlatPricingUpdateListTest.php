<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateDependentPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;

/**
 * Checks that a mass prices update via Batch API does not produce per price reindexation messages
 * when the flat pricing storage is used, and that the bulk reindexation is still requested.
 *
 * @dbIsolationPerTest
 */
class ProductPriceFlatPricingUpdateListTest extends RestJsonApiUpdateListTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * A safety limit for the message consumption loop, it must never be reached.
     */
    private const int MAX_CONSUMPTION_ROUNDS = 20;

    private ?string $savedStorage = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadProductPrices::class]);

        $this->enableFlatPricingStorage();

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_flat');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restorePricingStorage();

        parent::tearDown();
    }

    public function testNoPerPriceReindexOnCreateEntities(): void
    {
        $priceList5Id = $this->getReference('price_list_5')->getId();
        $data = [
            'data' => [
                [
                    'type' => 'productprices',
                    'attributes' => [
                        'quantity' => 250,
                        'value' => '150.0000',
                        'currency' => 'EUR'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => (string)$priceList5Id]
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-5->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.milliliter->code)>']
                        ]
                    ]
                ],
                [
                    'type' => 'productprices',
                    'attributes' => [
                        'quantity' => 10,
                        'value' => '20.0000',
                        'currency' => 'GBP'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => (string)$priceList5Id]
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.bottle->code)>']
                        ]
                    ]
                ]
            ]
        ];

        $sentMessages = $this->processUpdateListAndCollectSentMessages(ProductPrice::class, $data);

        self::assertCount(
            0,
            $this->getPerPriceReindexMessages($sentMessages),
            'Per price reindexation messages should not be sent for prices written by Batch API'
        );
        self::assertCount(
            1,
            $this->getMessagesByTopic($sentMessages, GenerateDependentPriceListPricesTopic::getName()),
            'The bulk versioned prices processing should be requested'
        );
        self::assertCount(
            1,
            $this->getBulkReindexMessages($sentMessages),
            'Products should be reindexed by the bulk job'
        );
    }

    /**
     * @param array<int, array{topic: string, message: array}> $sentMessages
     *
     * @return array<int, array{topic: string, message: array}>
     */
    private function getMessagesByTopic(array $sentMessages, string $topic): array
    {
        return array_values(
            array_filter($sentMessages, static fn (array $sentMessage) => $sentMessage['topic'] === $topic)
        );
    }

    /**
     * Reindexation messages sent by the bulk job carry a job ID, per price ones do not.
     *
     * @param array<int, array{topic: string, message: array}> $sentMessages
     *
     * @return array<int, array{topic: string, message: array}>
     */
    private function getPerPriceReindexMessages(array $sentMessages): array
    {
        return array_values(array_filter(
            $this->getMessagesByTopic($sentMessages, WebsiteSearchReindexTopic::getName()),
            static fn (array $sentMessage) => !isset($sentMessage['message']['jobId'])
        ));
    }

    /**
     * @param array<int, array{topic: string, message: array}> $sentMessages
     *
     * @return array<int, array{topic: string, message: array}>
     */
    private function getBulkReindexMessages(array $sentMessages): array
    {
        return array_values(array_filter(
            $this->getMessagesByTopic($sentMessages, WebsiteSearchReindexTopic::getName()),
            static fn (array $sentMessage) => isset($sentMessage['message']['jobId'])
        ));
    }

    /**
     * Same as {@see RestJsonApiUpdateListTestCase::processUpdateList()}, but collects all messages
     * sent while the batch operation is processed.
     *
     * Processing a message produces the messages of the next step of the chain, so the queue is consumed
     * round by round until it stays empty. The security token has to be restored after every round,
     * because it is reset together with the container when a message is consumed.
     *
     * @return array<int, array{topic: string, message: array}>
     */
    private function processUpdateListAndCollectSentMessages(string $entityClass, array $data): array
    {
        $operationId = $this->sendUpdateListRequest($entityClass, $data);

        $sentMessages = [];
        $round = 0;
        while ($sentMessagesCount = count(self::getSentMessages())) {
            if (++$round > self::MAX_CONSUMPTION_ROUNDS) {
                self::fail(
                    sprintf('The message queue was not drained in %d rounds.', self::MAX_CONSUMPTION_ROUNDS)
                );
            }

            $tokenStorage = $this->getTokenStorage();
            $token = $tokenStorage->getToken();

            $sentMessages[] = self::getSentMessages();
            self::clearMessageCollector();
            self::consume($sentMessagesCount);
            self::clearProcessedMessages();

            $tokenStorage->setToken($token);
        }

        $this->assertAsyncOperationErrors([], $operationId);

        return $sentMessages ? array_merge(...$sentMessages) : [];
    }

    private function enableFlatPricingStorage(): void
    {
        $configManager = $this->getGlobalConfigManager();
        $this->savedStorage = $configManager->get('oro_pricing.price_storage');
        $configManager->set('oro_pricing.price_storage', 'flat');
        $configManager->flush();
        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }

    private function restorePricingStorage(): void
    {
        if (null === $this->savedStorage) {
            return;
        }

        $configManager = $this->getGlobalConfigManager();
        $configManager->set('oro_pricing.price_storage', $this->savedStorage);
        $configManager->flush();
        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
        $this->savedStorage = null;
    }

    private function getGlobalConfigManager(): ConfigManager
    {
        return self::getConfigManager('global');
    }
}
