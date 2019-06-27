<?php

namespace Oro\Bundle\VisibilityBundle\Command;

use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Used to calculate product visibility cache.
 */
class VisibilityCacheBuildCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'product:visibility:cache:build';

    /** @var CacheBuilderInterface */
    private $cacheBuilder;

    /**
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function __construct(CacheBuilderInterface $cacheBuilder)
    {
        $this->cacheBuilder = $cacheBuilder;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Calculate product visibility cache.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Start the process of building the cache</info>');
        $this->cacheBuilder->buildCache();
        $output->writeln('<info>The cache is updated successfully</info>');
    }
}
