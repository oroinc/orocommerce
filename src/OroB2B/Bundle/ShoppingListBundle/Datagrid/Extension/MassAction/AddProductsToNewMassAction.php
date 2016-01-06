<?php

namespace OroB2B\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Widget\WindowMassAction;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

class AddProductsToNewMassAction extends WindowMassAction
{
    /**
     * {@inheritdoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (!isset($options['frontend_type'])) {
            $options['frontend_type'] = 'add-products-to-new-mass';
        }

        if (!isset($options['handler'])) {
            $options['handler'] = 'orob2b_shopping_list.mass_action.add_products_handler';
        }

        if (!isset($options['route'])) {
            $options['route'] = 'orob2b_shopping_list_add_products_to_new_massaction';
        }

        if (!isset($options['frontend_options'])) {
            $options['frontend_options'] = [
                'title' => 'orob2b.shoppinglist.widget.add_to_new_shopping_list',
                'regionEnabled' => false,
                'incrementalPosition' => false,
                'dialogOptions' => [
                    'modal' => true,
                    'resizable' => false,
                    'width' => 480,
                    'autoResize' => true,
                    'dialogClass' => 'shopping-list-dialog'
                ],
                'alias' => 'add_prodiucts_to_new_shopping_list_mass_action'
            ];
        }

        return parent::setOptions($options);
    }
}
