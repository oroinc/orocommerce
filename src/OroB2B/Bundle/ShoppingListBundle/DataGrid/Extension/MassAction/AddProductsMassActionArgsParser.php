<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;

class AddProductsMassActionArgsParser
{
    const CURRENT_SHOPPING_LIST_KEY = 'current';

    /**
     * @var array
     */
    protected $args;

    /**
     * @param MassActionHandlerArgs $args
     */
    public function __construct(MassActionHandlerArgs $args)
    {
        $this->args = $args->getData();
    }

    /**
     * @return array
     */
    public function getProductIds()
    {
        $productIds = [];
        if (!$this->isAllSelected() && array_key_exists('values', $this->args)) {
            $productIds = explode(',', $this->args['values']);
        }

        return $productIds;
    }

    /**
     * @return int|null
     */
    public function getShoppingListId()
    {
        $shoppingListId = $this->args['shoppingList'];

        if ($shoppingListId === self::CURRENT_SHOPPING_LIST_KEY) {
            $shoppingListId = null;
        }

        return $shoppingListId;
    }

    /**
     * @return bool
     */
    protected function isAllSelected()
    {
        return (array_key_exists('inset', $this->args) && (int)$this->args['inset'] === 0);
    }
}
