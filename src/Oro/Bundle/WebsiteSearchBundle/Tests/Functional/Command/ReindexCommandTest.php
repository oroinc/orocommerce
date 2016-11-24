<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ReindexCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCommand()
    {
        $result = $this->runCommand(
            'oro:website-search:reindex',
            [
                '--website-id' => '123',
                '--class' => 'OroTestFrameworkBundle:TestProduct'
            ]
        );

        $expectedOutput = <<<COUT
Starting reindex task for "Oro\Bundle\TestFrameworkBundle\Entity\TestProduct" and website id 123...
Reindex finished successfully.

COUT;

        $this->assertEquals($expectedOutput, $result);
    }
}
