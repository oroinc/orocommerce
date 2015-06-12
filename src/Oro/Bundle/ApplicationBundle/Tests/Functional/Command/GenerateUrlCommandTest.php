<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Functional\Command;

use Oro\Bundle\ApplicationBundle\Command\GenerateUrlCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GenerateUrlCommandTest extends WebTestCase
{
    public function testExecute()
    {
        $this->initClient();

        $result = $this->runCommand(
            GenerateUrlCommand::NAME,
            ['oro_default', json_encode(['qwe' => 'rty'])]
        );
        $result = trim($result);
        $this->assertStringEndsWith(
            sprintf('/admin.php/%s/?qwe=rty', ltrim($this->getContainer()->getParameter('backend_prefix'), '/')),
            $result
        );
    }
}
