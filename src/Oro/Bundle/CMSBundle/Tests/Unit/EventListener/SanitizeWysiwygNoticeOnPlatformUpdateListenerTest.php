<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Oro\Bundle\CMSBundle\Command\SanitizeWysiwygStyleFieldsCommand;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\DependencyInjection\OroCMSExtension;
use Oro\Bundle\CMSBundle\EventListener\SanitizeWysiwygNoticeOnPlatformUpdateListener;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InstallerBundle\Command\PlatformUpdateCommand;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class SanitizeWysiwygNoticeOnPlatformUpdateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private ConfigManager $configManager;

    private SanitizeWysiwygNoticeOnPlatformUpdateListener $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new SanitizeWysiwygNoticeOnPlatformUpdateListener($this->configManager);
    }

    public function testOnConsoleTerminateWhenNotPlatformUpdate(): void
    {
        $output = new OutputStub();
        $event = new ConsoleTerminateEvent(
            $this->createMock(Command::class),
            new InputStub(PlatformUpdateCommand::getDefaultName()),
            $output,
            0
        );

        $this->configManager
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onConsoleTerminate($event);

        self::assertEquals('', $output->getOutput());
    }

    public function testOnConsoleTerminateWhenNoForce(): void
    {
        $output = new OutputStub();
        $event = new ConsoleTerminateEvent(
            $this->createMock(PlatformUpdateCommand::class),
            new InputStub(PlatformUpdateCommand::getDefaultName()),
            $output,
            0
        );

        $this->configManager
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onConsoleTerminate($event);

        self::assertEquals('', $output->getOutput());
    }

    public function testOnConsoleTerminateWhenNotSuccessful(): void
    {
        $output = new OutputStub();
        $event = new ConsoleTerminateEvent(
            $this->createMock(PlatformUpdateCommand::class),
            new InputStub(PlatformUpdateCommand::getDefaultName(), [], ['force' => true]),
            $output,
            1
        );

        $this->configManager
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onConsoleTerminate($event);

        self::assertEquals('', $output->getOutput());
    }

    public function testOnConsoleTerminateWhenAlreadyShown(): void
    {
        $output = new OutputStub();
        $event = new ConsoleTerminateEvent(
            $this->createMock(PlatformUpdateCommand::class),
            new InputStub(PlatformUpdateCommand::getDefaultName(), [], ['force' => true]),
            $output,
            0
        );

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(OroCMSExtension::ALIAS . '.' . Configuration::IS_SANITIZE_WYSIWYG_NOTICE_SHOWN)
            ->willReturn(true);

        $this->listener->onConsoleTerminate($event);

        self::assertEquals('', $output->getOutput());
    }

    public function testOnConsoleTerminateWhenNotShown(): void
    {
        $output = new OutputStub();
        $event = new ConsoleTerminateEvent(
            $this->createMock(PlatformUpdateCommand::class),
            new InputStub(PlatformUpdateCommand::getDefaultName(), [], ['force' => true]),
            $output,
            0
        );

        $key = OroCMSExtension::ALIAS . '.' . Configuration::IS_SANITIZE_WYSIWYG_NOTICE_SHOWN;
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with($key)
            ->willReturn(false);

        $this->configManager
            ->expects(self::once())
            ->method('set')
            ->with($key);

        $this->configManager
            ->expects(self::once())
            ->method('flush');

        $this->listener->onConsoleTerminate($event);

        self::assertStringContainsString(
            sprintf('Please run "%s" Symfony command', SanitizeWysiwygStyleFieldsCommand::getDefaultName()),
            $output->getOutput()
        );
    }
}
