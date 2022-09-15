<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductDefaultAttributeFamily;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductInventoryStatuses;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchDeleteTopic;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class WebsiteSearchDeleteProcessorTest extends WebTestCase
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
            '@OroWebsiteSearchBundle/Tests/Functional/DataFixtures/WebsiteSearchSaveDeleteProcessorFixture.yml',
        ]);

        self::reindexProductData();
        self::ensureItemsLoaded(Product::class, 1);
    }

    public function testProcessWhenMessageIsInvalid(): void
    {
        $message = self::sendMessage(WebsiteSearchDeleteTopic::getName(), ['invalid_key' => 'invalid_value']);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.delete_processor', $message);

        self::ensureItemsLoaded(Product::class, 1);
    }

    public function testProcess(): void
    {
        $message = self::sendMessage(
            WebsiteSearchDeleteTopic::getName(),
            ['entity' => [['class' => Product::class, 'id' => $this->getReference('product1')->getId()]]]
        );

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);
        self::assertProcessedMessageProcessor('oro_website_search.async.delete_processor', $message);

        self::ensureItemsLoaded(Product::class, 0);
    }
}
