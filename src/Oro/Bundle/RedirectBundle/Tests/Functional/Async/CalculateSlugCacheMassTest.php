<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Async;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheJobAwareTopic;
use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheMassTopic;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Test\Functional\SlugAwareTestTrait;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;

/**
 * @dbIsolationPerTest
 */
class CalculateSlugCacheMassTest extends WebTestCase
{
    use MessageQueueExtension;
    use JobsAwareTestTrait;
    use SlugAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testProcess(): void
    {
        $this->loadFixtures([
            LoadSlugsData::class,
        ]);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(1, $page1->getSlugs());
        self::assertNoSlugsCache($page1);

        /** @var Page $page3 */
        $page3 = $this->getReference(LoadPageData::PAGE_3);
        self::assertCount(2, $page3->getSlugs());
        self::assertNoSlugsCache($page3);

        $sentMessage = self::sendMessage(CalculateSlugCacheMassTopic::getName(), [
            DirectUrlMessageFactory::ID => [],
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => Page::class,
        ]);

        self::consume(2);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.url_cache_mass_job_processor', $sentMessage);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(1, $page1->getSlugs());
        self::assertSlugsCache($page1);

        /** @var Page $page3 */
        $page3 = $this->getReference(LoadPageData::PAGE_3);
        self::assertCount(2, $page3->getSlugs());
        self::assertSlugsCache($page3);

        $sentChildMessage = self::getSentMessage(CalculateSlugCacheJobAwareTopic::getName(), false);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentChildMessage);
        self::assertProcessedMessageProcessor(
            'oro_redirect.async.url_cache_job_processor.job_runner',
            $sentChildMessage
        );
        $this->assertJobStatus(
            Job::STATUS_SUCCESS,
            $sentChildMessage->getBody()[CalculateSlugCacheJobAwareTopic::JOB_ID]
        );
    }

    public function testProcessWhenNoSlugs(): void
    {
        $this->loadFixtures([
            LoadPageData::class,
        ]);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(0, $page1->getSlugs());

        $sentMessage = self::sendMessage(CalculateSlugCacheMassTopic::getName(), [
            DirectUrlMessageFactory::ID => [],
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => Page::class,
        ]);

        self::consume(2);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.url_cache_mass_job_processor', $sentMessage);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(0, $page1->getSlugs());
    }

    public function testProcessWhenNotSluggable(): void
    {
        $sentMessage = self::sendMessage(CalculateSlugCacheMassTopic::getName(), [
            DirectUrlMessageFactory::ID => [],
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => \stdClass::class,
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.url_cache_mass_job_processor', $sentMessage);
    }
}
