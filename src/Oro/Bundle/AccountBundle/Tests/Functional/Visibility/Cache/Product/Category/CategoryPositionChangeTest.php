<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\CatalogBundle\Entity\Category;

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
        $this->markTestSkipped('Will be done in scope BB-4124');
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        /** @var Category $newParentCategory */
        $newParentCategory = $this->getReference($newParentCategoryReference);

        $category->setParentCategory($newParentCategory);

        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:Visibility\CategoryVisibility')
            ->flush();

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
