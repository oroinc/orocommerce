<?php

namespace Oro\Bundle\ApplicationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand as SymfonyCacheClearCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Bundle\InstallerBundle\CommandExecutor;

class CacheClearCommand extends SymfonyCacheClearCommand
{
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->getDefinition()->getOption('app')->setDefault(null);
        if (!$input->getOption('app')) {
            $hosts = array_keys($this->getContainer()->getParameter('application_hosts'));
            $commandExecutor = new CommandExecutor(
                $input->hasOption('env') ? $input->getOption('env') : null,
                $output,
                $this->getApplication(),
                $this->getContainer()->get('oro_cache.oro_data_cache_manager')
            );
            foreach ($hosts as $host) {
                $output->write($host.': ');
                $commandExecutor->runCommand(
                    'cache:clear',
                    array(
                        '--app'                 => $host,
                        '--no-optional-warmers' => $input->getOption('no-optional-warmers'),
                        '--no-warmup' => $input->getOption('no-warmup'),
                        '--process-isolation'   => true
                    )
                );
            }
        } else {
            parent::execute($input, $output);
        }
    }

    /**
     * @inheritdoc
     */
    protected function getTempKernel(KernelInterface $parent, $namespace, $parentClass, $warmupDir)
    {
        /** @var \AppKernel $kernel */
        $kernel = parent::getTempKernel($parent, $namespace, $parentClass, $warmupDir);
        $kernel->setApplication($this->getContainer()->getParameter('kernel.application'));

        return $kernel;
    }
}
