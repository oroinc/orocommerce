<?php

namespace Oro\Bundle\ProductBundle\Layout\Block\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class BreadcrumbsNavigationBlockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $currentCategory = $options['currentCategory'];

        $parentCategories = $this->getParentCategories($currentCategory);

        $view->vars['currentCategory'] = $currentCategory;
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
        return 'product_search_navigation';
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
