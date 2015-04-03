<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CacheClearCommandTest extends WebTestCase
{
    public function testExecute()
    {
        $this->initClient();

        /** @var KernelInterface $kernel */
        $kernel = $this->getContainer()->get('kernel');
        $application = new Application($kernel);

        /** @var Bundle $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            $bundle->registerCommands($application);
        }

        $command = $application->find('cache:clear');
        $this->assertInstanceOf('Oro\Bundle\ApplicationBundle\Command\CacheClearCommand', $command);
    }
}
