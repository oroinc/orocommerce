<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 * @group CommunityEdition
 */
class WebsiteProviderTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadWebsiteData::class ]);
    }

    public function testGetWebsites()
    {
        $websites = $this->getContainer()->get('oro_website.website.provider')->getWebsites();
        $this->assertCount(1, $websites);
    }
}
