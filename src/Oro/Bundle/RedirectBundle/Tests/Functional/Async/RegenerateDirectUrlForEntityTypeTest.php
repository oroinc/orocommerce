<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Async;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageSlugPrototypeData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesJobAwareTopic;
use Oro\Bundle\RedirectBundle\Async\Topic\RegenerateDirectUrlForEntityTypeTopic;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Test\Functional\SlugAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;

/**
 * @dbIsolationPerTest
 */
class RegenerateDirectUrlForEntityTypeTest extends WebTestCase
{
    use MessageQueueExtension;
    use JobsAwareTestTrait;
    use SlugAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadPageSlugPrototypeData::class,
        ]);
    }

    public function testProcess(): void
    {
        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(0, $page1->getSlugs());

        /** @var Page $page2 */
        $page2 = $this->getReference(LoadPageData::PAGE_2);
        self::assertCount(0, $page2->getSlugs());

        $sentMessage = self::sendMessage(RegenerateDirectUrlForEntityTypeTopic::getName(), [
            DirectUrlMessageFactory::ID => [],
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => Page::class,
            DirectUrlMessageFactory::CREATE_REDIRECT => false,
        ]);

        self::consume(2);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.sluggable_entities_processor', $sentMessage);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        $page1ExpectedSlugsCollection = $this->getExpectedSlugs($page1, 2);
        self::assertSlugs($page1ExpectedSlugsCollection, $page1);
        self::assertSlugsCache($page1);

        /** @var Page $page2 */
        $page2 = $this->getReference(LoadPageData::PAGE_2);
        $page2ExpectedSlugsCollection = $this->getExpectedSlugs($page2, 1);
        self::assertSlugs($page2ExpectedSlugsCollection, $page2);
        self::assertSlugsCache($page2);

        $sentChildMessage = self::getSentMessage(GenerateDirectUrlForEntitiesJobAwareTopic::getName(), false);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentChildMessage);
        self::assertProcessedMessageProcessor(
            'oro_redirect.async.direct_url_processor.job_runner',
            $sentChildMessage
        );
        $this->assertJobStatus(
            Job::STATUS_SUCCESS,
            $sentChildMessage->getBody()[GenerateDirectUrlForEntitiesJobAwareTopic::JOB_ID]
        );
    }
}
