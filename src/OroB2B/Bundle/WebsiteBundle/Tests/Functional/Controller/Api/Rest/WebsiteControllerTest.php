<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class WebsiteControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader()));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData'
            ]
        );
    }

    public function testDelete()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $websiteId = $website->getId();

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_website', ['id' => $websiteId]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->followRedirects();
        $this->client->request('GET', $this->getUrl('orob2b_website_view', ['id' => $websiteId]));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }
}
