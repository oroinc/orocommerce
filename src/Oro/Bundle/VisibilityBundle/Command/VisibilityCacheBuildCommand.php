<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Command;

use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Rebuilds the product visibility cache.
 */
#[AsCommand(
    name: 'product:visibility:cache:build',
    description: 'Rebuilds the product visibility cache.'
)]
class VisibilityCacheBuildCommand extends Command
{
    private CacheBuilderInterface $cacheBuilder;

    public function __construct(CacheBuilderInterface $cacheBuilder)
    {
        $this->cacheBuilder = $cacheBuilder;

        parent::__construct();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Start the process of building the cache</info>');
        $this->cacheBuilder->buildCache();
        $output->writeln('<info>The cache is updated successfully</info>');

        return Command::SUCCESS;
    }
}
