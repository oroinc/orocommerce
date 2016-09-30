<?php

namespace Oro\Bundle\CustomerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CustomerBundle\Visibility\Cache\Product\CacheBuilder;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
            ->addOption(
                'website_id',
                'w',
                InputOption::VALUE_OPTIONAL,
                'Website id for calculation cache'
            )
            ->setDescription('Calculate product visibility cache.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var CacheBuilder $cacheBuilder */
        $cacheBuilder = $this->getContainer()->get('oro_customer.visibility.cache.cache_builder');

        /** @var Website|null $website */
        $website = null;
        $forWebsiteStr = 'for all websites';
        if (null !== $input->getOption('website_id')) {
            $website = $this->getContainer()
                ->get('doctrine')
                ->getManagerForClass('OroWebsiteBundle:Website')
                ->getRepository('OroWebsiteBundle:Website')
                ->find((int)$input->getOption('website_id'));
            if (!$website instanceof Website) {
                $output->writeln('<error>Website id is not valid</error>');

                return;
            }
            $forWebsiteStr = sprintf('for website "%s"', $website->getName());
        }
        $output->writeln(
            sprintf('<info>Start the process of building the cache %s</info>', $forWebsiteStr)
        );

        $cacheBuilder->buildCache($website);
        $output->writeln('<info>The cache is updated successfully</info>');
    }
}
