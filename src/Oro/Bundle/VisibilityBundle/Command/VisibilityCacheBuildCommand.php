<?php

namespace Oro\Bundle\VisibilityBundle\Command;

use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\CacheBuilder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VisibilityCacheBuildCommand extends ContainerAwareCommand
{
    const NAME = 'product:visibility:cache:build';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Calculate product visibility cache.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var CacheBuilder $cacheBuilder */
        $cacheBuilder = $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder');
        $output->writeln('<info>Start the process of building the cache</info>');
        $cacheBuilder->buildCache();
        $output->writeln('<info>The cache is updated successfully</info>');
    }
}
