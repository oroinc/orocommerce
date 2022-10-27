<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductDefaultAttributeFamily;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductInventoryStatuses;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchResetIndexTopic;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class WebsiteSearchResetIndexProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use WebsiteSearchExtensionTrait;

    protected function setUp(): void
    {
        $this->initClient();

        self::purgeMessageQueue();

        $this->loadFixtures([
            LoadOrganization::class,
            LoadProductUnits::class,
            LoadProductInventoryStatuses::class,
            LoadProductDefaultAttributeFamily::class,
            '@OroWebsiteSearchBundle/Tests/Functional/DataFixtures/WebsiteSearchResetIndexProcessorFixture.yml',
        ]);

        self::reindexProductData();
        self::ensureItemsLoaded(Product::class, 2);
    }

    public function testProcessWhenMessageIsInvalid(): void
    {
        $message = self::sendMessage(WebsiteSearchResetIndexTopic::getName(), ['invalid_key' => 'invalid_value']);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reset_index_processor', $message);

        self::ensureItemsLoaded(Product::class, 2);
    }

    public function testProcess(): void
    {
        $message = self::sendMessage(WebsiteSearchResetIndexTopic::getName(), ['class' => Product::class]);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.reset_index_processor', $message);

        self::ensureItemsLoaded(Product::class, 0);
    }
}
