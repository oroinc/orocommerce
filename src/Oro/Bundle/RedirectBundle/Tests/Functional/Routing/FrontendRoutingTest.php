<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Routing;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class FrontendRoutingTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->getOptionalListenerManager()->enableListener('oro_redirect.event_listener.slug_prototype_change');
        $this->getOptionalListenerManager()->enableListener('oro_redirect.event_listener.slug_change');

        $this->loadFixtures([LoadSlugsData::class]);
    }

    private function getSlugTargetUrl(Slug $slug): string
    {
        $configManager = self::getConfigManager();
        $sluggableUrlsEnabled = $configManager->get('oro_redirect.enable_direct_url');
        $configManager->set('oro_redirect.enable_direct_url', false);
        $configManager->flush();
        try {
            $slugTargetUrl = $this->getUrl($slug->getRouteName(), $slug->getRouteParameters());
        } finally {
            $configManager->set('oro_redirect.enable_direct_url', $sluggableUrlsEnabled);
            $configManager->flush();
        }

        self::assertStringStartsNotWith('/slug/', $slugTargetUrl);

        return $slugTargetUrl;
    }

    public function testSlugRouting(): void
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);

        $crawler = $this->client->request('GET', $this->getSlugTargetUrl($slug));
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString($page->getContent(), $crawler->html());

        $crawler = $this->client->request('GET', LoadSlugsData::SLUG_URL_ANONYMOUS);
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString($page->getContent(), $crawler->html());
    }

    public function testSlugRoutingAuthentication(): void
    {
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_USER);

        $this->client->request('GET', $this->getSlugTargetUrl($slug));
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 401);

        $this->client->request('GET', LoadSlugsData::SLUG_URL_USER);
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 401);

        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->client->followRedirects();

        $crawler = $this->client->request('GET', $this->getSlugTargetUrl($slug));
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
        $pageTitle = $crawler->filter('title')->first()->html();

        $crawler = $this->client->request('GET', LoadSlugsData::SLUG_URL_USER);
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
        $slugPageTitle = $crawler->filter('title')->first()->html();

        $this->assertEquals($pageTitle, $slugPageTitle);
    }
}
