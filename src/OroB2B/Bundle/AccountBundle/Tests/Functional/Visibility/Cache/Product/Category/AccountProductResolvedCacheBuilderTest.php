<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountProductResolvedCacheBuilderTest extends WebTestCase
{
    /** @var Registry */
    protected $registry;
    
    /** @var Category */
    protected $category;

    /** @var Account */
    protected $account;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
        ]);

        $this->registry = $this->client->getContainer()->get('doctrine');
        $this->category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $this->account = $this->getReference('account.level_1');
    }
    
    public function tearDown()
    {
        $this->getContainer()->get('doctrine')->getManager()->clear();
        parent::tearDown();
    }

    public function testChangeAccountCategoryVisibilityToHidden()
    {
        $visibility = new AccountCategoryVisibility();
        $visibility->setCategory($this->category);
        $visibility->setAccount($this->account);
        $visibility->setVisibility(CategoryVisibility::HIDDEN);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility');
        $em->persist($visibility);
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @depends testChangeAccountCategoryVisibilityToHidden
     */
    public function testChangeAccountCategoryVisibilityToVisible()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CategoryVisibility::VISIBLE);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility');
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeAccountCategoryVisibilityToHidden
     */
    public function testChangeAccountCategoryVisibilityToAll()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountCategoryVisibility::CATEGORY);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility');
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals($visibility, $visibilityResolved->getSourceCategoryVisibility());
        $this->assertEquals(BaseCategoryVisibilityResolved::SOURCE_STATIC, $visibilityResolved->getSource());
        $this->assertEquals($this->category, $visibilityResolved->getCategory());
        $this->assertEquals(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE, $visibilityResolved->getVisibility());
    }

    /**
     * @depends testChangeAccountCategoryVisibilityToAll
     */
    public function testChangeAccountCategoryVisibilityToParentCategory()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountCategoryVisibility::PARENT_CATEGORY);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility');
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals($visibility, $visibilityResolved->getSourceCategoryVisibility());
        $this->assertEquals(BaseCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY, $visibilityResolved->getSource());
        $this->assertEquals($this->category, $visibilityResolved->getCategory());
        $this->assertEquals(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE, $visibilityResolved->getVisibility());
    }

    /**
     * @depends testChangeAccountCategoryVisibilityToParentCategory
     */
    public function testChangeAccountCategoryVisibilityToAccountGroup()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountCategoryVisibility::ACCOUNT_GROUP);

        $this->assertNotNull($this->getVisibilityResolved());

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility');
        $em->flush();

        $this->assertNull($this->getVisibilityResolved());
    }

    /**
     * @return null|AccountCategoryVisibilityResolved
     */
    protected function getVisibilityResolved()
    {
        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved');
        $entity = $em->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->findByPrimaryKey($this->category, $this->account);

        if ($entity) {
            $em->refresh($entity);
        }

        return $entity;
    }

    /**
     * @return null|AccountCategoryVisibility
     */
    protected function getVisibility()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountCategoryVisibility')
            ->findOneBy(['category' => $this->category, 'account' => $this->account]);
    }

    /**
     * @param AccountCategoryVisibilityResolved $categoryVisibilityResolved
     * @param VisibilityInterface $categoryVisibility
     * @param integer $expectedVisibility
     */
    protected function assertStatic(
        AccountCategoryVisibilityResolved $categoryVisibilityResolved,
        VisibilityInterface $categoryVisibility,
        $expectedVisibility
    ) {
        $this->assertNotNull($categoryVisibilityResolved);
        $this->assertEquals($this->category, $categoryVisibilityResolved->getCategory());
        $this->assertEquals($this->account, $categoryVisibilityResolved->getAccount());
        $this->assertEquals(AccountCategoryVisibilityResolved::SOURCE_STATIC, $categoryVisibilityResolved->getSource());
        $this->assertEquals($categoryVisibility, $categoryVisibilityResolved->getSourceCategoryVisibility());
        $this->assertEquals($expectedVisibility, $categoryVisibilityResolved->getVisibility());
    }
}
