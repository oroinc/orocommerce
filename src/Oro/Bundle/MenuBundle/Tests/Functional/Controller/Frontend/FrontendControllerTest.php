<?php

namespace Oro\Bundle\MenuBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;

use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolation
 */
class FrontendControllerTest extends FrontendWebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        parent::setUp();
    }

    public function testIndex()
    {
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->setCurrentWebsite('default');

        /** @var \Knp\Menu\ItemInterface $menu */
        $menu = $this->getContainer()->get('oro_menu.menu_provider')->get('main-menu');
        if (!$menu) {
            $this->markTestSkipped('There is no "main-menu" in system.');
        }

        $crawler = $this->client->request('GET', '/about'); // any page, CMS used as a fastest one
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $menuHtml = $crawler->filter('ul.main-menu')->text();

        /** @var \Knp\Menu\ItemInterface $menuItem */
        foreach ($menu->getChildren() as $menuItem) {
            $this->assertContains($menuItem->getLabel(), $menuHtml);
        }
    }
}
