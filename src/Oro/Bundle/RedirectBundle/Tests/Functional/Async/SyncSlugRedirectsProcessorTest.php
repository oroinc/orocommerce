<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Async;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\RedirectBundle\Async\Topic\SyncSlugRedirectsTopic;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Test\Functional\SlugAwareTestTrait;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadRedirects;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class SyncSlugRedirectsProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use SlugAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadSlugsData::class,
            LoadRedirects::class,
            LoadCustomers::class,
        ]);
    }

    public function testProcess(): void
    {
        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        self::assertContains($slug, $page1->getSlugs());
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_1);
        self::assertContains($redirect, $slug->getRedirects());

        self::assertCount(0, $slug->getScopes());
        self::assertCount(0, $redirect->getScopes());

        $slug->addScope($this->createScopeWithCustomer());
        $entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Slug::class);
        $entityManager->persist($slug);
        $entityManager->flush($slug);

        self::assertCount(1, $slug->getScopes());
        self::assertCount(0, $redirect->getScopes());

        $sentMessage = self::sendMessage(SyncSlugRedirectsTopic::getName(), [
            SyncSlugRedirectsTopic::SLUG_ID => $slug->getId(),
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.sync_slug_redirects_processor', $sentMessage);

        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_1);

        self::assertCount(1, $slug->getScopes());
        self::assertCount(1, $redirect->getScopes());
        self::assertEquals($slug->getScopes()[0]->getId(), $redirect->getScopes()[0]->getId());
    }

    public function testProcessWheNoScopes(): void
    {
        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        self::assertContains($slug, $page1->getSlugs());
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_1);
        self::assertContains($redirect, $slug->getRedirects());

        self::assertCount(0, $slug->getScopes());
        self::assertCount(0, $redirect->getScopes());

        $sentMessage = self::sendMessage(SyncSlugRedirectsTopic::getName(), [
            SyncSlugRedirectsTopic::SLUG_ID => $slug->getId(),
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.sync_slug_redirects_processor', $sentMessage);

        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_1);

        self::assertCount(0, $slug->getScopes());
        self::assertCount(0, $redirect->getScopes());
    }

    public function testProcessWheNoSlug(): void
    {
        $sentMessage = self::sendMessage(SyncSlugRedirectsTopic::getName(), [
            SyncSlugRedirectsTopic::SLUG_ID => PHP_INT_MAX,
        ]);

        self::consume(1);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor('oro_redirect.async.sync_slug_redirects_processor', $sentMessage);
    }

    private function createScopeWithCustomer(): Scope
    {
        return self::getContainer()
            ->get('oro_scope.scope_manager')
            ->findOrCreate('web_content', ['customer' => $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME)]);
    }
}
