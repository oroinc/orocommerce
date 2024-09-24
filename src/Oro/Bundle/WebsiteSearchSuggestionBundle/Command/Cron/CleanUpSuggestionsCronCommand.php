<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Command\Cron;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion\DeleteOrphanSuggestionsTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clean up outdated website search suggestions.
 */
#[AsCommand(
    name: 'oro:cron:website-search-suggestions:clean-up',
    description: 'Clean up outdated website search suggestions.'
)]
class CleanUpSuggestionsCronCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private MessageProducerInterface $producer)
    {
        parent::__construct();

        $this->logger = new NullLogger();
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 0 * * *';
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        try {
            $this->producer->send(DeleteOrphanSuggestionsTopic::getName(), []);
            $symfonyStyle->info('Initiated the clean up of outdated website search suggestions.');
        } catch (\Throwable $exception) {
            $symfonyStyle->error('Failed to initiate the clean up of outdated website search suggestions.');

            $this->logger->error(
                'Failed to initiate the clean up of outdated website search suggestions: {message}',
                [
                    'exception' => $exception,
                    'message' => $exception->getMessage(),
                ]
            );

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
