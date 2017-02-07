<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Routing;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FrontendRoutingTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadSlugsData::class
            ]
        );
    }

    public function testSlugRouting()
    {
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl($slug->getRouteName(), $slug->getRouteParameters())
        );
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $pageHtml = $crawler->html();

        $crawler = $this->client->request(
            'GET',
            LoadSlugsData::SLUG_URL_ANONYMOUS
        );
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $slugPageHtml = $crawler->html();

        $this->assertEquals($pageHtml, $slugPageHtml);
    }

    public function testSlugRoutingAuthentication()
    {
        $this->markTestSkipped('Will be fixed in BB-7513');
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_USER);
        $this->client->request(
            'GET',
            $this->getUrl($slug->getRouteName(), $slug->getRouteParameters())
        );
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 401);

        $this->client->request(
            'GET',
            LoadSlugsData::SLUG_URL_USER
        );

        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 401);

        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->client->followRedirects(true);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl($slug->getRouteName(), $slug->getRouteParameters())
        );
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $pageTitle = $crawler->filter('title')->first()->html();

        $crawler = $this->client->request(
            'GET',
            LoadSlugsData::SLUG_URL_USER
        );

        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $slugPageTitle = $crawler->filter('title')->first()->html();

        $this->assertEquals($pageTitle, $slugPageTitle);
    }
}
