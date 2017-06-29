<?php

namespace Oro\Bundle\SEOBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSitemapCommand extends ContainerAwareCommand
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
}
