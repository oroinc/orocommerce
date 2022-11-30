<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Async;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadBrandData;
use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheJobAwareTopic;
use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheMassTopic;
use Oro\Bundle\RedirectBundle\Async\Topic\RemoveDirectUrlForEntityTypeTopic;
use Oro\Bundle\RedirectBundle\Test\Functional\SlugAwareTestTrait;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;

/**
 * @dbIsolationPerTest
 */
class RemoveDirectUrlForEntityTypeTest extends WebTestCase
{
    use MessageQueueExtension;
    use JobsAwareTestTrait;
    use SlugAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadBrandData::class,
            LoadSlugsData::class,
        ]);
    }

    public function testProcess(): void
    {
        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertGreaterThan(0, count($page1->getSlugs()));

        self::getContainer()
            ->get('oro_redirect.cache.dumper.sluggable_url_dumper')
            ->dump($page1);
        self::assertSlugsCache($page1);

        $sentMessage = self::sendMessage(RemoveDirectUrlForEntityTypeTopic::getName(), Page::class);

        self::consume(3);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.direct_url_remove', $sentMessage);

        $page1 = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Page::class)
            ->find(Page::class, $page1->getId());

        self::assertCount(0, $page1->getSlugs());

        $sentCalculateSlugCacheMassMessage = self::getSentMessage(CalculateSlugCacheMassTopic::getName(), false);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentCalculateSlugCacheMassMessage);
        self::assertProcessedMessageProcessor(
            'oro_redirect.async.url_cache_mass_job_processor',
            $sentCalculateSlugCacheMassMessage
        );

        $sentCalculateSlugCacheMessage = self::getSentMessage(CalculateSlugCacheJobAwareTopic::getName(), false);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentCalculateSlugCacheMessage);
        self::assertProcessedMessageProcessor(
            'oro_redirect.async.url_cache_job_processor.job_runner',
            $sentCalculateSlugCacheMessage
        );

        $this->assertJobStatus(
            Job::STATUS_SUCCESS,
            $sentCalculateSlugCacheMessage->getBody()[CalculateSlugCacheJobAwareTopic::JOB_ID]
        );
    }

    public function testProcessWhenNoSlugs(): void
    {
        /** @var Brand $brand */
        $brand = $this->getReference(LoadBrandData::BRAND_1);

        self::assertCount(0, $brand->getSlugs());

        $sentMessage = self::sendMessage(RemoveDirectUrlForEntityTypeTopic::getName(), Brand::class);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.direct_url_remove', $sentMessage);

        $brand = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Brand::class)
            ->find(Brand::class, $brand->getId());

        self::assertCount(0, $brand->getSlugs());
    }

    public function testProcessWhenNotSluggable(): void
    {
        $sentMessage = self::sendMessage(RemoveDirectUrlForEntityTypeTopic::getName(), \stdClass::class);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.direct_url_remove', $sentMessage);
    }
}
