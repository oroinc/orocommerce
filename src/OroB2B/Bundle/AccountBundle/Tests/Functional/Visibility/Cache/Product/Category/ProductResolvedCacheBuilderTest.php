<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\AbstractQuery;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class ProductResolvedCacheBuilderTest extends WebTestCase
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

    public function testChangeCategoryVisibilityToHidden()
    {
        $visibility = new CategoryVisibility();
        $visibility->setCategory($this->category);
        $visibility->setVisibility(CategoryVisibility::HIDDEN);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\CategoryVisibility');
        $em->persist($visibility);
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @depends testChangeCategoryVisibilityToHidden
     */
    public function testChangeCategoryVisibilityToVisible()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CategoryVisibility::VISIBLE);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\CategoryVisibility');
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeCategoryVisibilityToHidden
     */
    public function testChangeCategoryVisibilityToConfig()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CategoryVisibility::CONFIG);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\CategoryVisibility');
        $em->flush();

        $this->assertNull($this->getVisibilityResolved());
    }

    /**
     * @depends testChangeCategoryVisibilityToConfig
     */
    public function testChangeCategoryVisibilityToParentCategory()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CategoryVisibility::PARENT_CATEGORY);

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\CategoryVisibility');
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertNull($visibilityResolved['sourceCategoryVisibility']['visibility']);
        $this->assertEquals(BaseCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY, $visibilityResolved['source']);
        $this->assertEquals($this->category->getId(), $visibilityResolved['category_id']);
        $this->assertEquals(
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $visibilityResolved['visibility']
        );
    }

    /**
     * @return array
     */
    protected function getVisibilityResolved()
    {
        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved');
        $qb = $em->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->createQueryBuilder('CategoryVisibilityResolved');
        $entity = $qb->select('CategoryVisibilityResolved', 'CategoryVisibility')
            ->leftJoin('CategoryVisibilityResolved.sourceCategoryVisibility', 'CategoryVisibility')
            ->where(
                $qb->expr()->eq('CategoryVisibilityResolved.category', ':category')
            )
            ->setParameters([
                'category' => $this->category,
            ])
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        return $entity;
    }

    /**
     * @return null|CategoryVisibility
     */
    protected function getVisibility()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\CategoryVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\CategoryVisibility')
            ->findOneBy(['category' => $this->category]);
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
        $this->assertEquals(CategoryVisibilityResolved::SOURCE_STATIC, $categoryVisibilityResolved['source']);
        $this->assertEquals(
            $categoryVisibility->getVisibility(),
            $categoryVisibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals($expectedVisibility, $categoryVisibilityResolved['visibility']);
    }
}
