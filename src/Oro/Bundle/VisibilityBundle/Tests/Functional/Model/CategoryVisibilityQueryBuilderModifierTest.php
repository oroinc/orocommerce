<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Model;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Model\CategoryVisibilityQueryBuilderModifier;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;

class CategoryVisibilityQueryBuilderModifierTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var CategoryVisibilityQueryBuilderModifier
     */
    private $categoryVisibilityQueryBuilderModifier;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadCategoryVisibilityData::class
        ]);

        $this->getContainer()->get('oro_visibility.visibility.cache.product.category.cache_builder')->buildCache();

        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->categoryVisibilityQueryBuilderModifier = new CategoryVisibilityQueryBuilderModifier(
            $doctrineHelper,
            self::getConfigManager(null),
            $this->getContainer()->get('oro_scope.scope_manager')
        );

        $this->queryBuilder = $doctrineHelper->getEntityManagerForClass(Category::class)->createQueryBuilder();
        $this->queryBuilder
            ->select('category.id')
            ->from(Category::class, 'category')
            ->andWhere('category.parentCategory IS NOT NULL');
    }

    public function testNotVisibleForAnonymousFiltered()
    {
        $this->categoryVisibilityQueryBuilderModifier->restrictForAnonymous($this->queryBuilder);

        $expectedResult = [
            LoadCategoryData::FIRST_LEVEL,
            LoadCategoryData::SECOND_LEVEL1,
            LoadCategoryData::SECOND_LEVEL2
        ];
        $actualResult = array_map(function ($category) {
            return $category['id'];
        }, $this->queryBuilder->getQuery()->getArrayResult());

        foreach ($expectedResult as $expectedReference) {
            /** @var Category $category */
            $category = $this->getReference($expectedReference);
            $this->assertContains(
                $category->getId(),
                $actualResult,
                "Expected category title - '{$category->getTitle()}'."
            );
        }
    }
}
