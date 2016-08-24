<?php

namespace Oro\Bundle\WebsiteSearchBundle\Layout\Block\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class SearchResultsNavigationBlockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $currentCategory = $options['currentCategory'];

        $parentCategories = $this->getParentCategories($currentCategory);

        $view->vars['parentCategories'] = $parentCategories;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'currentCategory' => null
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'website_search_product_grid_navigation';
    }

    /**
     * @param Category $category
     * @return \Oro\Bundle\CatalogBundle\Entity\Category[]
     */
    private function getParentCategories(Category $category)
    {
        $parentCategories = [];

        $currentCategory = $category->getParentCategory();

        while ($currentCategory) {
            $parentCategories[] = $currentCategory;

            $currentCategory = $currentCategory->getParentCategory();
        }

        $parentCategories = array_reverse($parentCategories);

        return $parentCategories;
    }
}