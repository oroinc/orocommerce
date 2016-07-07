<?php

namespace OroB2B\Bundle\CatalogBundle\Bundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

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
        $this->loadFixtures(['OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData']);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_frontend_root'));
        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $menuHtml = $crawler->filter('ul.top-nav__list')->text();

        /** @var AccountUser $loggedUser */
        $loggedUser = $this->getContainer()->get('oro_security.security_facade')->getLoggedUser();
        $categories = $this->getContainer()->get('orob2b_catalog.provider.category_tree_provider')->getCategories(
            $loggedUser,
            null,
            false
        );

        /** @var Category[] $categories */
        $categories = $categories[0]->getChildCategories()->toArray();
        // "categories_main_menu" layout block has option "max_size" with value 4
        $categories = array_slice($categories, 0, 4);

        foreach ($categories as $categoryFirstLevel) {
            $this->assertContains((string)$categoryFirstLevel->getDefaultTitle(), $menuHtml);
            foreach ($categoryFirstLevel->getChildCategories() as $categorySecondLevel) {
                $this->assertContains((string)$categorySecondLevel->getDefaultTitle(), $menuHtml);
                foreach ($categorySecondLevel->getChildCategories() as $categoryThirdLevel) {
                    $this->assertContains((string)$categoryThirdLevel->getDefaultTitle(), $menuHtml);
                }
            }
        }
    }
}
