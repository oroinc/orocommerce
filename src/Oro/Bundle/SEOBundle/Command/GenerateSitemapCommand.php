<?php

namespace Oro\Bundle\SEOBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\SEOBundle\EventListener\UpdateCronDefinitionConfigListener;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that adds message to queue for generating sitemap files
 */
class GenerateSitemapCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:sitemap:generate';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Add message to queue for generating sitemap files.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scheduleGenerationProvider = $this->getContainer()
            ->get('oro_seo.provider.sitemap_generation_scheduler');
        $scheduleGenerationProvider->scheduleSend();
        $output->writeln('<info>Sitemap generation scheduled</info>');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        $configManager = $this->getContainer()->get('oro_config.manager');
        return $configManager->get(UpdateCronDefinitionConfigListener::CONFIG_FIELD);
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return true;
    }
}
