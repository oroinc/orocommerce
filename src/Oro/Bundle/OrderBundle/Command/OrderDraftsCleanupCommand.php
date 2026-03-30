<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\OrderBundle\Async\Topic\OrderDraftsCleanupTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Initiates cleanup of outdated draft orders and order line items via message queue.
 */
#[AsCommand(
    name: 'oro:cron:draft-session:cleanup:order',
    description: 'Initiates cleanup of outdated draft orders and order line items via message queue.'
)]
class OrderDraftsCleanupCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    private const int DEFAULT_DRAFT_LIFETIME_DAYS = 7;
    private const string OPTION_DRAFT_LIFETIME = 'draft-lifetime';

    public function __construct(
        private readonly MessageProducerInterface $messageProducer
    ) {
        parent::__construct();
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        // 00:00 every day
        return '0 0 * * *';
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_DRAFT_LIFETIME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Draft lifetime in days',
                self::DEFAULT_DRAFT_LIFETIME_DAYS
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command initiates cleanup of outdated draft orders and order line items
via message queue.

Draft orders and line items older than the specified lifetime (default: 7 days from the last update)
will be deleted asynchronously via message queue. Make sure that the message consumer processes
(<info>oro:message-queue:consume</info>) are running for the scheduled deletions to be executed.

  <info>php %command.full_name%</info>

You can change the draft lifetime using the <info>--draft-lifetime</info> option:

  <info>php %command.full_name% --draft-lifetime=60</info>

HELP
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $draftLifetime = (int)$input->getOption(self::OPTION_DRAFT_LIFETIME);

        if ($draftLifetime <= 0) {
            $symfonyStyle->error('Draft lifetime must be a positive integer.');

            return Command::FAILURE;
        }

        $this->messageProducer->send(
            OrderDraftsCleanupTopic::getName(),
            [
                'draftLifetimeDays' => $draftLifetime
            ]
        );

        $symfonyStyle->success(
            sprintf(
                'Initiated cleanup of outdated draft orders and line items older than %d days.',
                $draftLifetime
            )
        );

        return Command::SUCCESS;
    }
}
