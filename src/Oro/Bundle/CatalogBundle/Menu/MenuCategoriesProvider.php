<?php

namespace Oro\Bundle\CatalogBundle\Menu;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Provides categories data for using in menu.
 */
class MenuCategoriesProvider implements MenuCategoriesProviderInterface
{
    private CategoryTreeProvider $categoryTreeProvider;

    private LocalizationHelper $localizationHelper;

    public function __construct(
        CategoryTreeProvider $categoryTreeProvider,
        LocalizationHelper $localizationHelper
    ) {
        $this->categoryTreeProvider = $categoryTreeProvider;
        $this->localizationHelper = $localizationHelper;
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
        ?Localization $localization = null,
        array $context = []
    ): array {
        $categories = $this->categoryTreeProvider->getCategories($user, $category, true);
        $categoriesData = [];
        foreach ($categories as $eachCategory) {
            $categoryId = $eachCategory->getId();
            $categoriesData[$categoryId] = [
                'id' => $categoryId,
                'parentId' => $eachCategory->getParentCategory()?->getId(),
                'title' => $this->localizationHelper
                    ->getLocalizedValue($eachCategory->getTitles(), $localization),
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
