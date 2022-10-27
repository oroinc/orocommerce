<?php
declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Command;

use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Rebuilds the product visibility cache.
 */
class VisibilityCacheBuildCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'product:visibility:cache:build';

    private CacheBuilderInterface $cacheBuilder;

    public function __construct(CacheBuilderInterface $cacheBuilder)
    {
        $this->cacheBuilder = $cacheBuilder;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Rebuilds the product visibility cache.');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Start the process of building the cache</info>');
        $this->cacheBuilder->buildCache();
        $output->writeln('<info>The cache is updated successfully</info>');

        return 0;
    }
}
