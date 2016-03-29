<?php

namespace OroB2B\Bundle\CatalogBundle\Bundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

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
        $this->loadFixtures(
            [
                'OroB2B\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData',
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('_frontend'));
        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $menuHtml = $crawler->filter('ul.top-nav__list')->text();

        /** @var AccountUser $loggedUser */
        $loggedUser = $this->getContainer()->get('oro_security.security_facade')->getLoggedUser();
        $categories = $this->getContainer()->get('orob2b_catalog.provider.category_tree_provider')->getCategories(
            $loggedUser,
            null,
            null
        );

        if ($categories) {
            $categories = $categories[0]->getChildCategories()->toArray();
            $categories = array_slice($categories, 0, 4);

            foreach ($categories[0]->getChildCategories() as $category) {
                $this->assertContains((string)$category->getDefaultTitle(), $menuHtml);
            }
        }

    }
}
