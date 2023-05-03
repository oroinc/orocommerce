<?php

namespace Oro\Bundle\CatalogBundle\Menu;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tools\LocalizedFallbackValueHelper;
use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Provides categories data for using in menu.
 */
class MenuCategoriesProvider implements MenuCategoriesProviderInterface
{
    private CategoryTreeProvider $categoryTreeProvider;

    public function __construct(CategoryTreeProvider $categoryTreeProvider)
    {
        $this->categoryTreeProvider = $categoryTreeProvider;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $context
     *  [
     *      'tree_depth' => int, // Max depth to expand categories children. -1 stands for unlimited.
     *  ]
     */
    public function getCategories(
        Category $category,
        ?UserInterface $user = null,
        array $context = []
    ): array {
        $categories = $this->categoryTreeProvider->getCategories($user, $category, true);
        $categoriesData = [];
        foreach ($categories as $eachCategory) {
            $categoryId = $eachCategory->getId();
            $categoriesData[$categoryId] = [
                'id' => $categoryId,
                'parentId' => $eachCategory->getParentCategory()?->getId(),
                'titles' => LocalizedFallbackValueHelper::cloneCollection(
                    $eachCategory->getTitles(),
                    LocalizedFallbackValue::class
                ),
                'level' => $eachCategory->getLevel(),
            ];
        }

        $this->applyTreeDepth($categoriesData, $context['tree_depth'] ?? -1);

        return $categoriesData;
    }

    private function applyTreeDepth(array &$categoriesData, int $treeDepth): void
    {
        if ($treeDepth > -1) {
            $baseCategoryData = reset($categoriesData);
            foreach ($categoriesData as $key => $categoryData) {
                if ($categoryData['level'] - $baseCategoryData['level'] > $treeDepth) {
                    unset($categoriesData[$key]);
                }
            }
        }
    }
}
