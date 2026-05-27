<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Command;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\OrderBundle\Async\Topic\OrderDraftsCleanupTopic;
use Oro\Bundle\OrderBundle\Command\OrderDraftsCleanupCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;
use Symfony\Component\Console\Command\Command;

class OrderDraftsCleanupCommandTest extends WebTestCase
{
    use MessageQueueExtension;
    use CommandTestingTrait;

    private const string COMMAND_NAME = 'oro:cron:draft-session:cleanup:order';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testCommandImplementsCronInterface(): void
    {
        $command = self::getContainer()->get('oro_order.command.order_drafts_cleanup');
        self::assertInstanceOf(CronCommandScheduleDefinitionInterface::class, $command);
    }

    public function testGetDefaultDefinition(): void
    {
        /** @var OrderDraftsCleanupCommand $command */
        $command = self::getContainer()->get('oro_order.command.order_drafts_cleanup');
        self::assertEquals('0 0 * * *', $command->getDefaultDefinition());
    }

    public function testExecuteWithDefaultDraftLifetime(): void
    {
        $commandTester = $this->doExecuteCommand(self::COMMAND_NAME);

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains(
            $commandTester,
            'Initiated cleanup of outdated draft orders and line items older than 7 days'
        );

        self::assertMessageSent(
            OrderDraftsCleanupTopic::getName(),
            ['draftLifetimeDays' => 7]
        );
    }

    public function testExecuteWithCustomDraftLifetime(): void
    {
        $commandTester = $this->doExecuteCommand(self::COMMAND_NAME, ['--draft-lifetime' => 60]);

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains(
            $commandTester,
            'Initiated cleanup of outdated draft orders and line items older than 60 days'
        );

        self::assertMessageSent(
            OrderDraftsCleanupTopic::getName(),
            ['draftLifetimeDays' => 60]
        );
    }

    public function testExecuteWithInvalidDraftLifetime(): void
    {
        $commandTester = $this->doExecuteCommand(self::COMMAND_NAME, ['--draft-lifetime' => -10]);

        self::assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertOutputContains(
            $commandTester,
            'Draft lifetime must be a positive integer'
        );

        self::assertMessagesEmpty(OrderDraftsCleanupTopic::getName());
    }

    public function testExecuteWithZeroDraftLifetime(): void
    {
        $commandTester = $this->doExecuteCommand(self::COMMAND_NAME, ['--draft-lifetime' => 0]);

        self::assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertOutputContains(
            $commandTester,
            'Draft lifetime must be a positive integer'
        );

        self::assertMessagesEmpty(OrderDraftsCleanupTopic::getName());
    }
}
