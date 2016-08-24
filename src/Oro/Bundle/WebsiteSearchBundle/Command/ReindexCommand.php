<?php

namespace Oro\Bundle\WebsiteSearchBundle\Command;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:website_search:reindex';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->addOption(
                'class',
                null,
                InputOption::VALUE_OPTIONAL,
                'Full or compact class name of entity which should be reindexed' .
                '(e.g. Oro\Bundle\UserBundle\Entity\User or OroUserBundle:User)'
            )
            ->addOption(
                'website_id',
                null,
                InputOption::VALUE_OPTIONAL,
                'INTEGER. Website ID need to reindex '
            )
            ->setDescription('Rebuild search index for certain website/entity type or all mapped entities');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getOption('class');
        $websiteId = $input->getOption('website_id');

        $placeholder = 'all mapped entities';
        if ($class) {
            // convert from short format to FQСN
            $class = $this->getContainer()->get('doctrine')
                ->getManagerForClass($class)->getClassMetadata($class)->getName();
            $placeholder = '"' . $class . '" entity';
        }

        $context = [];

        if ($websiteId) {
            $placeholder .= ' and website id ' . $websiteId;
            $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY] = $websiteId;
        }

        $output->writeln('Starting reindex task for ' . $placeholder);

        /** @var $searchEngine IndexerInterface */
        $indexer = $this->getContainer()->get('oro_website_search.indexer');

        $recordsCount = $indexer->reindex($class, $context);

        $output->writeln(sprintf('Total indexed items: %u', $recordsCount));
    }
}
