<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Symfony\Component\Yaml\Yaml;

/**
 * @dbIsolation
 */
class CategoryPositionChangeTest extends CategoryCacheTestCase
{
    /**
     * @dataProvider positionChangeDataProvider
     *
     * @param string $categoryReference
     * @param string $newParentCategoryReference
     * @param array $expectedData
     */
    public function testPositionChange($categoryReference, $newParentCategoryReference, array $expectedData)
    {
        $container = $this->getContainer();
        $container->get('oro_customer.visibility.cache.cache_builder')->buildCache();
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        /** @var Category $newParentCategory */
        $newParentCategory = $this->getReference($newParentCategoryReference);

        $category->setParentCategory($newParentCategory);

        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:Visibility\CategoryVisibility')
            ->flush();
        $container->get('oro_customer.visibility.cache.product.category.cache_builder')
            ->categoryPositionChanged($category);

        $this->assertProductVisibilityResolvedCorrect($expectedData);
    }

    /**
     * @return array
     */
    public function positionChangeDataProvider()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'expected_position_change.yml';

        return Yaml::parse(file_get_contents($file));
    }
}
