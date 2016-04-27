<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\AbstractQuery;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\PositionChangeCategorySubtreeCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeCategorySubtreeCacheBuilder;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\CategoryResolvedCacheBuilder;

/**
 * @dbIsolation
 */
class CategoryResolvedCacheBuilderTest extends AbstractProductResolvedCacheBuilderTest
{
    /** @var CategoryResolvedCacheBuilder */
    protected $builder;

    /** @var Category */
    protected $category;

    protected function setUp()
    {
        parent::setUp();

        $this->category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $container = $this->client->getContainer();

        $this->builder = new CategoryResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_entity.orm.insert_from_select_query_executor')
        );
        $this->builder->setCacheClass(
            $container->getParameter('orob2b_account.entity.category_visibility_resolved.class')
        );

        $subtreeBuilder = new VisibilityChangeCategorySubtreeCacheBuilder(
            $container->get('doctrine'),
            $container->get('orob2b_account.visibility.resolver.category_visibility_resolver'),
            $container->get('oro_config.manager')
        );

        $this->builder->setVisibilityChangeCategorySubtreeCacheBuilder($subtreeBuilder);

        $positionChangeBuilder = new PositionChangeCategorySubtreeCacheBuilder(
            $container->get('doctrine'),
            $container->get('orob2b_account.visibility.resolver.category_visibility_resolver'),
            $container->get('oro_config.manager')
        );

        $this->builder->setPositionChangeCategorySubtreeCacheBuilder($positionChangeBuilder);
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
        $this->assertEquals(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE, $visibilityResolved['visibility']);
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
    public function testBuildCache()
    {
        $expectedVisibilities = [
            [
                'category' => self::ROOT,
                'visibility' => CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                'source' => CategoryVisibilityResolved::SOURCE_STATIC,
            ],
            [
                'category' => 'category_1',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CategoryVisibilityResolved::SOURCE_STATIC,
            ],
            [
                'category' => 'category_1_2',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            ],
            [
                'category' => 'category_1_2_3',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CategoryVisibilityResolved::SOURCE_STATIC,
            ],
            [
                'category' => 'category_1_2_3_4',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            ],
            [
                'category' => 'category_1_5',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            ],
            [
                'category' => 'category_1_5_6',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => CategoryVisibilityResolved::SOURCE_STATIC,
            ],
            [
                'category' => 'category_1_5_6_7',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            ],
        ];
        $expectedVisibilities = $this->replaceReferencesWithIds($expectedVisibilities);
        usort($expectedVisibilities, [$this, 'sortByCategory']);

        $this->builder->buildCache();

        $actualVisibilities = $this->getResolvedVisibilities();
        usort($actualVisibilities, [$this, 'sortByCategory']);

        $this->assertEquals($expectedVisibilities, $actualVisibilities);
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortByCategory(array $a, array $b)
    {
        return $a['category'] > $b['category'] ? 1 : -1;
    }

    /**
     * @param array $categories
     * @return array
     */
    protected function replaceReferencesWithIds(array $categories)
    {
        $rootCategory = $this->getRootCategory();

        foreach ($categories as $key => $row) {
            $category = $row['category'];
            /** @var Category $category */
            if ($category === self::ROOT) {
                $category = $rootCategory;
            } else {
                $category = $this->getReference($category);
            }
            $categories[$key]['category'] = $category->getId();
        }

        return $categories;
    }

    /**
     * @return array
     */
    protected function getResolvedVisibilities()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->createQueryBuilder('entity')
            ->select(
                'IDENTITY(entity.category) as category',
                'entity.visibility',
                'entity.source'
            )
            ->getQuery()
            ->getArrayResult();
    }
}
