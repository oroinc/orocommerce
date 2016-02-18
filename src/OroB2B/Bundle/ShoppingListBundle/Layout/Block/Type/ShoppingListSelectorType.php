<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class ShoppingListSelectorType extends AbstractType
{
    const NAME = 'shopping_list_selector';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['shoppingLists']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $selectedShoppingListId = null;

        if ($block->getContext()->data()->has('shoppingList')
            && $shoppingList = $block->getContext()->data()->get('shoppingList')
        ) {
            $selectedShoppingListId = $shoppingList->getId();
        }

        $view->vars['shoppingLists'] = $options['shoppingLists'];
        $view->vars['selectedShoppingList'] = $selectedShoppingListId;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
