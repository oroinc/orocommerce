<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Command;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\Command\ResizeAllProductImagesCommand;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\ProductImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ResizeAllProductImagesCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();

        $this->getOptionalListenerManager()->enableListener('oro_product.event_listener.product_image_resize_listener');
    }

    public function testRun()
    {
        $this->loadFixtures([ProductImageData::class]);
        $output = self::runCommand(ResizeAllProductImagesCommand::getDefaultName(), ['--force' => true]);

        $messagesQueued = self::getMessageCollector()
            ->getTopicSentMessages(Topics::PRODUCT_IMAGE_RESIZE);

        $this->assertCount(4, $messagesQueued);
        static::assertStringContainsString('4 product image(s) queued for resize', $output);
    }

    public function testRunNoImagesAvailable()
    {
        $output = self::runCommand(ResizeAllProductImagesCommand::getDefaultName(), ['--force' => true]);

        $messagesQueued = self::getMessageCollector()
            ->getTopicSentMessages(Topics::PRODUCT_IMAGE_RESIZE);

        $this->assertCount(0, $messagesQueued);
        static::assertStringContainsString('0 product image(s) queued for resize', $output);
    }
}
