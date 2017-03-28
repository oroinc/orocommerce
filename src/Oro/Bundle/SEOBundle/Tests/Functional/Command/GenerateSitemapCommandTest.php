<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Command;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SEOBundle\Async\Topics;
use Oro\Bundle\SEOBundle\Command\GenerateSitemapCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ReindexCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient();
    }

    public function testCommand()
    {
        self::runCommand(GenerateSitemapCommand::NAME, []);

        $traces = self::getMessageCollector()->getTopicSentMessages(Topics::GENERATE_SITEMAP);
        $this->assertCount(1, $traces);
        $this->assertEquals(['topic' => Topics::GENERATE_SITEMAP, 'message' => ''], $traces[0]);
    }
}
