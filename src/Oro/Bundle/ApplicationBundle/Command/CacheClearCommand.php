<?php

namespace Oro\Bundle\ApplicationBundle\Command;

use AppKernel;
use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand as SymfonyCacheClearCommand;
use Symfony\Component\HttpKernel\KernelInterface;

class CacheClearCommand extends SymfonyCacheClearCommand
{
    /**
     * @inheritdoc
     */
    protected function getTempKernel(KernelInterface $parent, $namespace, $parentClass, $warmupDir)
    {
        /** @var AppKernel $kernel */
        $kernel = parent::getTempKernel($parent, $namespace, $parentClass, $warmupDir);
        $kernel->setApplication($this->getContainer()->getParameter('kernel.application'));

        return $kernel;
    }
}
