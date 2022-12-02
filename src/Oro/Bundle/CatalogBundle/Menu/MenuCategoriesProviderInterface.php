<?php

namespace Oro\Bundle\CatalogBundle\Menu;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Interface for classes that provide categories data for using in menu.
 */
interface MenuCategoriesProviderInterface
{
    /**
     * Provides a list of children for $category, including self, ordered by position in a tree (left).
     *
     * @param Category $category
     * @param UserInterface|null $user
     * @param Localization|null $localization
     * @param array $context Arbitrary context options to take into account.
     *                       Look into specific provider for available options.
     *
     * @return array
     *  [
     *      int $categoryId => [
     *          'id' => int,
     *          'parentId' => int,
     *          'title' => string,
     *          'level' => int,
     *      ],
     *      // ...
     *  ]
     */
    public function getCategories(
        Category $category,
        ?UserInterface $user = null,
        ?Localization $localization = null,
        array $context = []
    ): array;
}
