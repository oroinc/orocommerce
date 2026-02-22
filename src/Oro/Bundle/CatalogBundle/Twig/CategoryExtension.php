<?php

namespace Oro\Bundle\CatalogBundle\Twig;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to work with categories:
 *   - oro_category_list
 *   - oro_product_category_full_path
 *   - oro_product_category_title
 */
class CategoryExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_category_list', [$this, 'getCategoryList']),
            new TwigFunction('oro_product_category_full_path', [$this, 'getProductCategoryPath']),
            new TwigFunction('oro_product_category_title', [$this, 'getProductCategoryTitle'])
        ];
    }

    /**
     * @param string|null $rootLabel
     * @param object|null $root
     *
     * @return array
     */
    public function getCategoryList($rootLabel = null, $root = null)
    {
        $tree = $this->getCategoryTreeHandler()->createTree($root);
        if ($rootLabel && array_key_exists(0, $tree)) {
            $tree[0]['text'] = $rootLabel;
        }

        return $tree;
    }

    public function getProductCategoryPath(Category $category): string
    {
        return implode(' / ', $this->getCategoriesTitles($category));
    }

    public function getProductCategoryTitle(Category $category): string
    {
        $categoriesTitles = $this->getCategoriesTitles($category);
        return count($categoriesTitles) <= 2
            ? implode(' / ', $categoriesTitles)
            : reset($categoriesTitles) . ' /.../ ' . end($categoriesTitles);
    }

    private function getCategoriesTitles(Category $category): array
    {
        $title = $category->getDefaultTitle();
        if (!$title) {
            return [];
        }

        $categoriesTitles = [$title];
        while ($category->getParentCategory() && $category->getParentCategory()->getDefaultTitle()) {
            $category = $category->getParentCategory();
            $categoriesTitles[] = $category->getDefaultTitle();
        }

        return array_reverse($categoriesTitles);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            CategoryTreeHandler::class
        ];
    }

    private function getCategoryTreeHandler(): CategoryTreeHandler
    {
        return $this->container->get(CategoryTreeHandler::class);
    }
}
