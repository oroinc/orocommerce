<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Command;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Command\ResizeAllProductImagesCommand;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\ProductImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ResizeAllProductImagesCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testRun()
    {
        $this->loadFixtures([ProductImageData::class]);
        $output = self::runCommand(ResizeAllProductImagesCommand::getDefaultName(), ['--force' => true]);

        $messagesQueued = self::getMessageCollector()
            ->getTopicSentMessages(ProductImageResizeListener::IMAGE_RESIZE_TOPIC);

        $this->assertCount(4, $messagesQueued);
        $this->assertContains('4 product image(s) queued for resize', $output);
    }

    public function testRunNoImagesAvailable()
    {
        $output = self::runCommand(ResizeAllProductImagesCommand::getDefaultName(), ['--force' => true]);

        $messagesQueued = self::getMessageCollector()
            ->getTopicSentMessages(ProductImageResizeListener::IMAGE_RESIZE_TOPIC);

        $this->assertCount(0, $messagesQueued);
        $this->assertContains('0 product image(s) queued for resize', $output);
    }
}
