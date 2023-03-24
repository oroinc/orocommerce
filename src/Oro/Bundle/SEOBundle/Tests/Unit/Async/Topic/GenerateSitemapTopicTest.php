<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;

class GenerateSitemapTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new GenerateSitemapTopic();
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro.seo.generate_sitemap',
            $this->getTopic()->createJobName([])
        );
    }
}
