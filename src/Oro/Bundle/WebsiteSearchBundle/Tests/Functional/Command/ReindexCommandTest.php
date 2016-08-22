<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ReindexCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
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
        $this->assertContains('Total indexed items: 12', $result);
    }
}
