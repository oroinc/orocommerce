<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Command;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Oro\Bundle\ProductBundle\Command\ResizeAllProductImagesCommand;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\ProductImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @group CommunityEdition
 */
class ResizeAllProductImagesCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();

        $this->getOptionalListenerManager()->enableListener('oro_product.event_listener.product_image_resize_listener');
    }

    public function testRun(): void
    {
        $this->loadFixtures([ProductImageData::class]);
        $output = self::runCommand(ResizeAllProductImagesCommand::getDefaultName(), ['--force' => true]);

        self::assertCountMessages(ResizeProductImageTopic::getName(), 4);
        self::assertStringContainsString('4 product image(s) queued for resize', $output);
    }

    public function testRunNoImagesAvailable(): void
    {
        $output = self::runCommand(ResizeAllProductImagesCommand::getDefaultName(), ['--force' => true]);

        self::assertMessagesEmpty(ResizeProductImageTopic::getName());
        self::assertStringContainsString('0 product image(s) queued for resize', $output);
    }
}
