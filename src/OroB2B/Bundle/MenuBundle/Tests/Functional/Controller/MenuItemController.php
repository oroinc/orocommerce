<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MenuItemController extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\MenuBundle\Tests\Functional\DataFixtures\LoadMenuItemData']);
    }

    public function testView()
    {
        $url = $this->getUrl('orob2b_menu_item_view', ['id' => $this->getReference('menu_item.1')->getId()]);
        $crawler = $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Menu Items', $crawler->filter('h1.oro-subtitle')->html());
        $this->assertContains(
            'Please select a menu item on the left or create new one.',
            $crawler->filter('.content .text-center')->html()
        );
    }
}
