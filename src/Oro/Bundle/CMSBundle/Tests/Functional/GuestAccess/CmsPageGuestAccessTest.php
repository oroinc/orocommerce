<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Functional\GuestAccess;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * Tests that CMS pages are accessible/blocked based on GUEST_ACCESS_ALLOWED_CMS_PAGES configuration
 * when guest access is disabled.
 */
class CmsPageGuestAccessTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?bool $initialGuestAccess = null;
    private ?array $initialAllowedCmsPages = null;
    private string $configKey;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPageData::class]);

        $this->configKey = Configuration::getConfigKeyByName(Configuration::GUEST_ACCESS_ALLOWED_CMS_PAGES);

        $configManager = self::getConfigManager();
        $this->initialGuestAccess = $configManager->get('oro_frontend.guest_access_enabled');
        $this->initialAllowedCmsPages = $configManager->get($this->configKey);

        $configManager->set('oro_frontend.guest_access_enabled', false);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_frontend.guest_access_enabled', $this->initialGuestAccess);
        $configManager->set($this->configKey, $this->initialAllowedCmsPages);
        $configManager->flush();
    }

    public function testCmsPageIsNotAccessibleWhenGuestAccessDisabled(): void
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);

        $url = $this->getUrl('oro_cms_frontend_page_view', ['id' => $page->getId()]);
        $this->client->request('GET', $url);
        $response = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($response, 302);
        self::assertTrue($response->isRedirect('/customer/user/login'));
    }

    public function testConfiguredCmsPageIsAccessibleWhenGuestAccessDisabled(): void
    {
        $configManager = self::getConfigManager();

        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);

        $configManager->set($this->configKey, [$page]);
        $configManager->flush();

        // Verify the CMS page is accessible even though guest access is disabled
        $url = $this->getUrl('oro_cms_frontend_page_view', ['id' => $page->getId()]);
        $this->client->request('GET', $url);
        $response = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($response, 200);
    }

    public function testNonConfiguredCmsPageIsNotAccessibleWhenGuestAccessDisabled(): void
    {
        $configManager = self::getConfigManager();

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        /** @var Page $page2 */
        $page2 = $this->getReference(LoadPageData::PAGE_2);

        // Configure only page 1 to be accessible
        $configManager->set($this->configKey, [$page1]);
        $configManager->flush();

        // Verify page 2 is still redirected
        $url = $this->getUrl('oro_cms_frontend_page_view', ['id' => $page2->getId()]);
        $this->client->request('GET', $url);
        $response = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($response, 302);
        self::assertTrue($response->isRedirect('/customer/user/login'));
    }

    public function testMultipleCmsPagesAreAccessibleWhenConfigured(): void
    {
        $configManager = self::getConfigManager();

        /** @var Page $page1 */
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        /** @var Page $page2 */
        $page2 = $this->getReference(LoadPageData::PAGE_2);

        // Configure both pages to be accessible
        $configManager->set($this->configKey, [$page1, $page2]);
        $configManager->flush();

        // Verify both pages are accessible
        $url1 = $this->getUrl('oro_cms_frontend_page_view', ['id' => $page1->getId()]);
        $this->client->request('GET', $url1);
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);

        $url2 = $this->getUrl('oro_cms_frontend_page_view', ['id' => $page2->getId()]);
        $this->client->request('GET', $url2);
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
    }
}
