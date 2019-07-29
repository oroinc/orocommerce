<?php

namespace Oro\Bundle\SEOBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\SEOBundle\Async\SitemapGenerationScheduler;
use Oro\Bundle\SEOBundle\EventListener\UpdateCronDefinitionConfigListener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that adds message to queue for generating sitemap files
 */
class GenerateSitemapCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:sitemap:generate';

    /** @var SitemapGenerationScheduler */
    private $sitemapGenerationScheduler;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param SitemapGenerationScheduler $sitemapGenerationScheduler
     * @param ConfigManager $configManager
     */
    public function __construct(SitemapGenerationScheduler $sitemapGenerationScheduler, ConfigManager $configManager)
    {
        $this->sitemapGenerationScheduler = $sitemapGenerationScheduler;
        $this->configManager = $configManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add message to queue for generating sitemap files.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->sitemapGenerationScheduler->scheduleSend();
        $output->writeln('<info>Sitemap generation scheduled</info>');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return $this->configManager->get(UpdateCronDefinitionConfigListener::CONFIG_FIELD);
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return true;
    }
}
