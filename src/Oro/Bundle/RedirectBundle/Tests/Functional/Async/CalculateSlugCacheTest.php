<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Async;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheTopic;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Test\Functional\SlugAwareTestTrait;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class CalculateSlugCacheTest extends WebTestCase
{
    use MessageQueueExtension;
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

        $sentMessage = self::sendMessage(CalculateSlugCacheTopic::getName(), [
            DirectUrlMessageFactory::ID => [$page1->getId()],
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => Page::class,
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.url_cache_job_processor', $sentMessage);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(1, $page1->getSlugs());
        self::assertSlugsCache($page1);
    }

    public function testProcessWhenNoSlugs(): void
    {
        $this->loadFixtures([
            LoadPageData::class,
        ]);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(0, $page1->getSlugs());

        $sentMessage = self::sendMessage(CalculateSlugCacheTopic::getName(), [
            DirectUrlMessageFactory::ID => [$page1->getId()],
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => Page::class,
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.url_cache_job_processor', $sentMessage);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(0, $page1->getSlugs());
    }

    public function testProcessWhenNoEntity(): void
    {
        $sentMessage = self::sendMessage(CalculateSlugCacheTopic::getName(), [
            DirectUrlMessageFactory::ID => [PHP_INT_MAX],
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => Page::class,
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.url_cache_job_processor', $sentMessage);
    }
}
