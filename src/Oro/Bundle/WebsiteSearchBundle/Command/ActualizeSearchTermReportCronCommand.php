<?php

namespace Oro\Bundle\WebsiteSearchBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\WebsiteSearchBundle\Manager\SearchResultHistoryManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Actualize Search Terms report.
 */
class ActualizeSearchTermReportCronCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    /** @var string */
    protected static $defaultName = 'oro:website-search:actualize-search-term-report';

    private SearchResultHistoryManagerInterface $manager;

    public function __construct(SearchResultHistoryManagerInterface $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    public function getDefaultDefinition()
    {
        // At minute 1 past every hour.
        return '1 */1 * * *';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Actualize Search Terms report.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command actualizes Search Terms report.
Every hour data is updated in the report. All history records that are older than 30 days are removed.

  <info>php %command.full_name%</info>

HELP
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Actualizing report');
        $this->manager->actualizeHistoryReport();
        $output->writeln('Removing records older than 30 days');
        $this->manager->removeOutdatedHistoryRecords();
        $output->writeln('Done');

        return self::SUCCESS;
    }
}
