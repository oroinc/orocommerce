<?php

namespace Oro\Bundle\MenuBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group segfault
 *
 * @dbIsolation
 */
class MenuItemControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['Oro\Bundle\MenuBundle\Tests\Functional\DataFixtures\LoadMenuItemData']);
    }

    public function testRoots()
    {
        $this->client->request('GET', $this->getUrl('oro_menu_item_roots'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testView()
    {
        $menuItem = $this->getReference('menu_item.1');
        $url = $this->getUrl('oro_menu_item_view', ['id' => $menuItem->getId()]);
        $crawler = $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($menuItem->getDefaultTitle()->getString(), $crawler->html());
        $this->assertContains(
            'Please select a menu item on the left or create new one.',
            $crawler->filter('.content .text-center')->html()
        );
    }

    public function testCreateRoot()
    {
        $title = 'Root title';

        $crawler = $this->client->request('GET', $this->getUrl('oro_menu_item_create_root'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_menu_item[defaultTitle]'] = $title;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $this->assertContains('Menu has been saved', $html);
    }

    public function testCreateChild()
    {
        $title = 'Child title';
        $uri = 'test/uri';

        $rootId = $this->getReference('menu_item.1')->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_menu_item_create', ['id' => $rootId]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $form['oro_menu_item[titles][values][default]'] = $title;
        $form['oro_menu_item[uri]'] = $uri;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $this->assertContains('Menu Item has been saved', $html);
        $this->assertContains($title, $html);
    }

    public function testUpdate()
    {
        $title = 'Child title';
        $uri = 'test/uri';

        $childId = $this->getReference('menu_item.1_2')->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_menu_item_update', ['id' => $childId]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $form['oro_menu_item[titles][values][default]'] = $title;
        $form['oro_menu_item[uri]'] = $uri;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $this->assertContains('Menu Item has been saved', $html);
        $this->assertContains($title, $html);
    }
}
