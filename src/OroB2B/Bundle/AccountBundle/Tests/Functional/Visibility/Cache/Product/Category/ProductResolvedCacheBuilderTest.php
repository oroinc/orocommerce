<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\ProductResolvedCacheBuilder;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class ProductResolvedCacheBuilderTest extends WebTestCase
{
    const ROOT = 'root';

    /**
     * @var ProductResolvedCacheBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
        ]);

        $this->builder = $this->getContainer()
            ->get('orob2b_account.visibility.cache.product.category.product_resolved_cache_builder');
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

    /**
     * @return Category
     */
    protected function getRootCategory()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getMasterCatalogRoot();
    }
}
