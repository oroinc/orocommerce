<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Command;

use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
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
 * Initiates the generation of website search suggestions for all products.
 */
#[AsCommand(
    name: 'oro:website-search-suggestions:generate',
    description: 'Generate website search suggestions for all products.'
)]
class GenerateSuggestionsCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private MessageProducerInterface $producer)
    {
        parent::__construct();

        $this->logger = new NullLogger();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        try {
            $this->producer->send(GenerateSuggestionsTopic::getName(), []);

            $symfonyStyle->info('Initiated the generation of website search suggestions for all products.');
        } catch (\Throwable $exception) {
            $symfonyStyle->error('Failed to initiate the generation of website search suggestions.');

            $this->logger->error(
                'Failed to initiate the generation of website search suggestions: {message}',
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
