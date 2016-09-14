<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Command;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ReindexCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $indexerMock = $this->getMockBuilder(IndexerInterface::class)->getMock();
        $indexerMock->expects($this->once())->method('reindex')->willReturn(12);
        $this->getContainer()->set('oro_website_search.indexer', $indexerMock);
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
