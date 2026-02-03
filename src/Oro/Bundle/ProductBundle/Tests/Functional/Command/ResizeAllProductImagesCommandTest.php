<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Command;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeAllProductImagesTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @group CommunityEdition
 */
class ResizeAllProductImagesCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testRunDefault(): void
    {
        $output = self::runCommand('product:image:resize-all');

        self::assertMessageSent(ResizeAllProductImagesTopic::getName(), [
            ResizeAllProductImagesTopic::FORCE => false,
            ResizeAllProductImagesTopic::DIMENSIONS => [],
        ]);
        self::assertStringContainsString('Product image resize has been scheduled', $output);
    }

    public function testRunWithForce(): void
    {
        $output = self::runCommand('product:image:resize-all', ['--force' => true]);

        self::assertMessageSent(ResizeAllProductImagesTopic::getName(), [
            ResizeAllProductImagesTopic::FORCE => true,
            ResizeAllProductImagesTopic::DIMENSIONS => [],
        ]);
        self::assertStringContainsString('Product image resize has been scheduled', $output);
    }

    public function testRunWithDimensions(): void
    {
        $output = self::runCommand('product:image:resize-all', [
            '--dimension' => 'large',
        ]);

        self::assertMessageSent(ResizeAllProductImagesTopic::getName(), [
            ResizeAllProductImagesTopic::FORCE => false,
            ResizeAllProductImagesTopic::DIMENSIONS => ['large'],
        ]);
        self::assertStringContainsString('Product image resize has been scheduled', $output);
    }

    public function testRunWithAllOptions(): void
    {
        $output = self::runCommand('product:image:resize-all', [
            '--force' => true,
            '--dimension' => 'original',
        ]);

        self::assertMessageSent(ResizeAllProductImagesTopic::getName(), [
            ResizeAllProductImagesTopic::FORCE => true,
            ResizeAllProductImagesTopic::DIMENSIONS => ['original'],
        ]);
        self::assertStringContainsString('Product image resize has been scheduled', $output);
    }
}
