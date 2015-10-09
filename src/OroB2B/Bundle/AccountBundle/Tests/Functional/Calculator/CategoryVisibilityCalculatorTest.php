<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Calculator;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class CategoryVisibilityCalculatorTest extends WebTestCase
{
    /** @var AccountUser */
    protected $user;

    protected $visibleCategories = [
        'category_1_2',
        'category_1_2_6',
        'category_1_2_6_8',
        'category_1_2_9',
        'category_1_10',
    ];

    protected $invisibleCategories = [
        'category_1_2_3',
        'category_1_2_3_4',
        'category_1_2_3_5',
        //'category_1_2_6_7'
    ];

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD)
        );

        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData']);
    }

    public function testCheckCalculatedCategories()
    {
        $treeData = $this->getTreeData();

        foreach ($this->visibleCategories as $categoryName) {
            $this->assertContains($categoryName, $treeData);
        }

        foreach ($this->invisibleCategories as $categoryName) {
            $this->assertNotContains($categoryName, $treeData);
        }
    }

    public function testChangeVisibility()
    {
        $this->changeVisibility();
        $treeData = $this->getTreeData();

        foreach ($this->visibleCategories as $categoryName) {
            $this->assertContains($categoryName, $treeData);
        }

        foreach ($this->invisibleCategories as $categoryName) {
            $this->assertNotContains($categoryName, $treeData);
        }
    }

    protected function changeVisibility()
    {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility');

//        $accountVisibility = $em->getRepository('OroB2BAccountBundle:Visibility\AccountCategoryVisibility')
//            ->findOneBy(['category' => 6, 'account' => 3]);

        $accountVisibility = new AccountCategoryVisibility();

        /** @var AccountUser $user */
        $user = $this->getReference(LoadAccountUserData::EMAIL);
//        $user = $em->getRepository('OroB2BAccountBundle:AccountUser')
//            ->findOneBy(['email' => LoadAccountUserData::EMAIL]);

//        $category = $em->getRepository('OroB2BCatalogBundle:Category')
//            ->find(6);
        /** @var Category $category */
        $category = $this->getReference('category_1_2_6');

        $accountVisibility->setAccount($user->getAccount());
        $accountVisibility->setCategory($category);
        $accountVisibility->setVisibility(AccountCategoryVisibility::HIDDEN);

        $em->persist($accountVisibility);
        $em->flush($accountVisibility);
    }

    /**
     * @return string
     */
    protected function getTreeData()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_frontend_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->filter('.category.b2b-tree div')->attr('data-page-component-options');
    }
}
