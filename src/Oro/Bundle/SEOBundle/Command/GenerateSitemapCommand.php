<?php

declare(strict_types=1);

namespace Oro\Bundle\SEOBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\SEOBundle\Async\SitemapGenerationScheduler;
use Oro\Bundle\SEOBundle\EventListener\UpdateCronDefinitionConfigListener;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Schedules generation of sitemap files.
 */
#[AsCommand(
    name: 'oro:cron:sitemap:generate',
    description: 'Schedules generation of sitemap files.'
)]
class GenerateSitemapCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    private SitemapGenerationScheduler $sitemapGenerationScheduler;
    private ConfigManager $configManager;

    public function __construct(SitemapGenerationScheduler $sitemapGenerationScheduler, ConfigManager $configManager)
    {
        $this->sitemapGenerationScheduler = $sitemapGenerationScheduler;
        $this->configManager = $configManager;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command schedules generation of sitemap files.

This command only schedules the job by adding a message to the message queue, so ensure
that the message consumer processes (<info>oro:message-queue:consume</info>) are running.

  <info>php %command.full_name%</info>

HELP
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->sitemapGenerationScheduler->scheduleSend();
        $output->writeln('<info>Sitemap generation scheduled</info>');

        return Command::SUCCESS;
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return $this->configManager->get(UpdateCronDefinitionConfigListener::CONFIG_FIELD);
    }
}
