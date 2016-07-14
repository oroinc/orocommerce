<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class FrontendControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    public function testIndex()
    {
        /** @var \Knp\Menu\ItemInterface $menu */
        $menu = $this->getContainer()->get('orob2b_menu.menu_provider')->get('main-menu');
        if (!$menu) {
            $this->markTestSkipped('There is no "main-menu" in system.');
        }

        $crawler = $this->client->request('GET', '/about'); // any page, CMS used as a fastest one
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $menuHtml = $crawler->filter('ul.top-nav__list')->text();

        /** @var \Knp\Menu\ItemInterface $menuItem */
        foreach ($menu->getChildren() as $menuItem) {
            $this->assertContains($menuItem->getLabel(), $menuHtml);
        }
    }
}
