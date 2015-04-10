<?php

namespace Oro\Bundle\ApplicationBundle\Script;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use Oro\Bundle\DistributionBundle\Script\Runner as DistributionRunner;

/**
 * TODO: After adding multiple application in platform this class will be merged with DistributionBundle Runner
 */
class Runner extends DistributionRunner
{
    /**
     * @inheritdoc
     */
    public function clearDistApplicationCache()
    {
        return $this->runCommand('cache:clear --no-warmup', 'install');
    }

    /**
     * @param string $command - e.g. clear:cache --no-warmup
     * @param string $application - console or dist
     *
     * @return string
     * @throws ProcessFailedException
     */
    protected function runCommand($command, $application = 'admin')
    {
        $phpPath = $this->getPhpExecutablePath();

        $command = sprintf(
            '"%s" "%s/console" %s --env=%s --app=%s',
            $phpPath,
            $this->applicationRootDir,
            $command,
            $this->environment,
            $application
        );

        $this->logger->info(sprintf('Executing "%s"', $command));

        $process = new Process($command);
        $process->setWorkingDirectory(realpath($this->applicationRootDir . '/..')); // project root
        $process->setTimeout(600);

        $process->run();

        if (!$process->isSuccessful()) {
            $processFailedException = new ProcessFailedException($process);
            $this->logger->error($processFailedException->getMessage());
            throw $processFailedException;
        }

        $output = $process->getOutput();
        $this->logger->info($output);

        return $output;
    }
}
