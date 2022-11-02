<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Symfony\Component\Yaml\Yaml;

/**
 * @group CommunityEdition
 */
class CategoryPositionChangeTest extends CategoryCacheTestCase
{
    /**
     * @dataProvider positionChangeDataProvider
     */
    public function testPositionChange(
        string $categoryReference,
        string $newParentCategoryReference,
        array $expectedData
    ) {
        $container = $this->getContainer();
        $container->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        /** @var Category $newParentCategory */
        $newParentCategory = $this->getReference($newParentCategoryReference);

        $category->setParentCategory($newParentCategory);

        $this->getContainer()->get('doctrine')
            ->getManagerForClass(CategoryVisibility::class)
            ->flush();
        $container->get('oro_visibility.visibility.cache.product.category.cache_builder')
            ->categoryPositionChanged($category);

        $this->assertProductVisibilityResolvedCorrect($expectedData);
    }

    public function positionChangeDataProvider(): array
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'expected_position_change.yml';

        return Yaml::parse(file_get_contents($file));
    }
}
