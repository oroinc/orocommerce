<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ReindexCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        //TODO: remove after services.yml will be loaded by dependency injection extension
        $this->getContainer()->set('oro_website_search.indexer', new OrmIndexerStub());
    }

    public function testCommand()
    {
        $result = $this->runCommand(
            'oro:website_search:reindex',
            [
                '--website_id' => '123',
                '--class' => 'OroUserBundle:User'
            ]
        );

        $expectedOutput = <<<COUT
Starting reindex task for "Oro\Bundle\UserBundle\Entity\User" entity and website id 123
Total indexed items: 12

COUT;

        $this->assertEquals($expectedOutput, $result);
    }
}
