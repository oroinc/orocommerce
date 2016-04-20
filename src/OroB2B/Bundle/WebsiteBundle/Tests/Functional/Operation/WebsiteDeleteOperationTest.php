<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class WebsiteDeleteOperationTest extends ActionTestCase
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

        $this->assertDeleteOperation($websiteId, 'orob2b_website.entity.website.class', 'orob2b_website_index');

        $this->client->followRedirects();
        $this->client->request('GET', $this->getUrl('orob2b_website_view', ['id' => $websiteId]));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }
}
