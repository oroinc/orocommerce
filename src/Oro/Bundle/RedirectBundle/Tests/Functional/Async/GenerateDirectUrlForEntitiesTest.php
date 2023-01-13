<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Async;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageSlugPrototypeData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Test\Functional\SlugAwareTestTrait;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class GenerateDirectUrlForEntitiesTest extends WebTestCase
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
            LoadPageSlugPrototypeData::class,
        ]);

        /** @var Page $page2 */
        $page2 = $this->getReference(LoadPageData::PAGE_2);
        self::assertCount(0, $page2->getSlugs());

        $sentMessage = self::sendMessage(GenerateDirectUrlForEntitiesTopic::getName(), [
            DirectUrlMessageFactory::ID => $page2->getId(),
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => Page::class,
            DirectUrlMessageFactory::CREATE_REDIRECT => false,
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.direct_url_processor', $sentMessage);

        /** @var Page $page2 */
        $page2 = $this->getReference(LoadPageData::PAGE_2);
        self::assertCount(1, $page2->getSlugs());
        self::assertSlugs($this->getExpectedSlugs($page2, 1), $page2);
        self::assertSlugsCache($page2);
    }

    public function testProcessWithNonSystemLocalization(): void
    {
        $this->loadFixtures([
            LoadPageSlugPrototypeData::class,
        ]);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(0, $page1->getSlugs());

        $sentMessage = self::sendMessage(GenerateDirectUrlForEntitiesTopic::getName(), [
            DirectUrlMessageFactory::ID => $page1->getId(),
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => Page::class,
            DirectUrlMessageFactory::CREATE_REDIRECT => false,
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.direct_url_processor', $sentMessage);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(2, $page1->getSlugs());
        self::assertSlugs($this->getExpectedSlugs($page1, 2), $page1);
        self::assertSlugsCache($page1);
        self::assertCount(2, $page1->getSlugs());
    }

    public function testProcessWithRedirect(): void
    {
        $this->loadFixtures([
            LoadSlugsData::class,
        ]);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(1, $page1->getSlugs());

        $page1->addSlugPrototype($this->createSlugPrototype('page-1-new'));
        $this->persistEntity($page1);

        $sentMessage = self::sendMessage(GenerateDirectUrlForEntitiesTopic::getName(), [
            DirectUrlMessageFactory::ID => $page1->getId(),
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => Page::class,
            DirectUrlMessageFactory::CREATE_REDIRECT => true,
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.direct_url_processor', $sentMessage);

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        self::assertCount(1, $page1->getSlugs());
        self::assertSlugs($this->getExpectedSlugs($page1, 1), $page1);
        self::assertSlugsCache($page1);
        self::assertCount(1, $page1->getSlugs());

        $systemSlug = self::findSlug($page1->getSlugs(), null);
        self::assertCount(1, $systemSlug->getRedirects());

        $redirect = $systemSlug->getRedirects()[0];
        self::assertEquals(LoadSlugsData::SLUG_URL_ANONYMOUS, $redirect->getFrom());
        self::assertEquals($systemSlug->getUrl(), $redirect->getTo());
    }

    public function testProcessWheNoEntity(): void
    {
        $sentMessage = self::sendMessage(GenerateDirectUrlForEntitiesTopic::getName(), [
            DirectUrlMessageFactory::ID => PHP_INT_MAX,
            DirectUrlMessageFactory::ENTITY_CLASS_NAME => Page::class,
            DirectUrlMessageFactory::CREATE_REDIRECT => true,
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.direct_url_processor', $sentMessage);
    }

    private function persistEntity(Page $page): void
    {
        $entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Page::class);
        $entityManager->persist($page);
        $entityManager->flush($page);
    }

    private function createSlugPrototype(string $string, ?Localization $localization = null): LocalizedFallbackValue
    {
        return (new LocalizedFallbackValue())
            ->setString($string)
            ->setLocalization($localization);
    }
}
