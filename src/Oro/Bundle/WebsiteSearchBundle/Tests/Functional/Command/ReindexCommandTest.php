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
                '--class' => 'OroUserBundle:User'
            ]
        );

        $expectedOutput = <<<COUT
Starting reindex task for "Oro\Bundle\UserBundle\Entity\User" and website id 123
Total indexed items: 12

COUT;

        $this->assertEquals($expectedOutput, $result);
    }
}
