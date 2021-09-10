<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class AddProductsMassActionArgsParser
{
    /**
     * @var array
     */
    protected $args;

    public function __construct(MassActionHandlerArgs $args)
    {
        $this->args = $args->getData();
    }

    /**
     * @return array
     */
    public function getUnitsAndQuantities()
    {
        $unitsAndQuantities = [];
        if (isset($this->args['units_and_quantities'])) {
            $unitsAndQuantities = json_decode($this->args['units_and_quantities'], true);
        }

        return $unitsAndQuantities;
    }

    /**
     * @return array
     */
    public function getProductIds()
    {
        $productIds = [];
        if (!$this->isAllSelected() && array_key_exists('values', $this->args)) {
            $productIds = array_map(
                function ($id) {
                    return (int) $id;
                },
                explode(',', $this->args['values'])
            );
        }

        return $productIds;
    }

    /**
     * Returns shopping list.
     *
     * @return ShoppingList|null
     */
    public function getShoppingList()
    {
        return array_key_exists('shoppingList', $this->args)
            ? $this->args['shoppingList']
            : null;
    }

    /**
     * @return bool
     */
    protected function isAllSelected()
    {
        return array_key_exists('inset', $this->args) && (int) $this->args['inset'] === 0;
    }
}
