<?php

namespace Oro\Bundle\SEOBundle\Command;

use Oro\Bundle\SEOBundle\Async\Topics;
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
        $messageProducer = $this->getContainer()->get('oro_message_queue.client.message_producer');
        $messageProducer->send(Topics::GENERATE_SITEMAP, '');
        $output->writeln('<info>Sitemap generation scheduled</info>');
    }
}
