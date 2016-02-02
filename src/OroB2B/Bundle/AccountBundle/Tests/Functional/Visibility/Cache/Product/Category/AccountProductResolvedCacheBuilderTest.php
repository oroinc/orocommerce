<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\AbstractQuery;

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
        $this->assertEquals(
            $visibility->getVisibility(),
            $visibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals(BaseCategoryVisibilityResolved::SOURCE_STATIC, $visibilityResolved['source']);
        $this->assertEquals($this->category->getId(), $visibilityResolved['category_id']);
        $this->assertEquals(
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $visibilityResolved['visibility']
        );
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
        $this->assertEquals(
            $visibility->getVisibility(),
            $visibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals(BaseCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY, $visibilityResolved['source']);
        $this->assertEquals($this->category->getId(), $visibilityResolved['category_id']);
        $this->assertEquals(
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $visibilityResolved['visibility']
        );
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
     * @return array
     */
    protected function getVisibilityResolved()
    {
        $em = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
        $qb = $em->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->createQueryBuilder('accountCategoryVisibilityResolved');
        $entity = $qb->select('accountCategoryVisibilityResolved', 'accountCategoryVisibility')
            ->leftJoin('accountCategoryVisibilityResolved.sourceCategoryVisibility', 'accountCategoryVisibility')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('accountCategoryVisibilityResolved.category', ':category'),
                    $qb->expr()->eq('accountCategoryVisibilityResolved.account', ':account')
                )
            )
            ->setParameters([
                'category' => $this->category,
                'account' => $this->account,
            ])
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

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
     * @param array $categoryVisibilityResolved
     * @param VisibilityInterface $categoryVisibility
     * @param integer $expectedVisibility
     */
    protected function assertStatic(
        array $categoryVisibilityResolved,
        VisibilityInterface $categoryVisibility,
        $expectedVisibility
    ) {
        $this->assertNotNull($categoryVisibilityResolved);
        $this->assertEquals($this->category->getId(), $categoryVisibilityResolved['category_id']);
        $this->assertEquals($this->account->getId(), $categoryVisibilityResolved['account_id']);
        $this->assertEquals(AccountCategoryVisibilityResolved::SOURCE_STATIC, $categoryVisibilityResolved['source']);
        $this->assertEquals(
            $categoryVisibility->getVisibility(),
            $categoryVisibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals($expectedVisibility, $categoryVisibilityResolved['visibility']);
    }
}
