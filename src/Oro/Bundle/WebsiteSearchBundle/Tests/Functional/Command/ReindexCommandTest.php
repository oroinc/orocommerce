<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Command;

use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;

class ReindexCommandTest extends WebTestCase
{
    use SearchExtensionTrait;

    protected function setUp()
    {
        $this->initClient();
    }

    protected function tearDown()
    {
        $this->clearIndexTextTable(IndexText::class);
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

        $expectedOutput = 'Starting reindex task for Oro\Bundle\TestFrameworkBundle\Entity\TestProduct ' .
            'and website ID 123...';
        $this->assertContains($expectedOutput, $result);
        $this->assertContains('Reindex finished successfully.', $result);
    }
}
