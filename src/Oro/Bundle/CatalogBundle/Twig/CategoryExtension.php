<?php

namespace Oro\Bundle\CatalogBundle\Twig;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CategoryExtension extends \Twig_Extension
{
    const NAME = 'oro_catalog_category_extension';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return CategoryTreeHandler
     */
    protected function getCategoryTreeHandler()
    {
        return $this->container->get('oro_catalog.category_tree_handler');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_category_list', [$this, 'getCategoryList']),
            new \Twig_SimpleFunction('oro_product_category_full_path', [$this, 'getProductCategoryPath']),
            new \Twig_SimpleFunction('oro_product_category_title', [$this, 'getProductCategoryTitle'])
        ];
    }

    /**
     * @param string|null $rootLabel
     *
     * @return array
     */
    public function getCategoryList($rootLabel = null)
    {
        $tree = $this->getCategoryTreeHandler()->createTree();
        if ($rootLabel && array_key_exists(0, $tree)) {
            $tree[0]['text'] = $rootLabel;
        }

        return $tree;
    }

    /**
     * @param Category $category
     *
     * @return string
     */
    public function getProductCategoryPath(Category $category): string
    {
        return implode(' / ', $this->getCategoriesTitles($category));
    }

    /**
     * @param Category $category
     *
     * @return string
     */
    public function getProductCategoryTitle(Category $category): string
    {
        $categoriesTitles = $this->getCategoriesTitles($category);
        return count($categoriesTitles) <= 2
            ? implode(' / ', $categoriesTitles)
            : reset($categoriesTitles) . ' /.../ ' . end($categoriesTitles);
    }

    /**
     * @param Category $category
     *
     * @return array
     */
    protected function getCategoriesTitles(Category $category): array
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
}
