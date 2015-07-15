<?php
namespace OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\AjaxMassAction;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

class AddProductsMassAction extends AjaxMassAction
{
    /**
     * @param ActionConfiguration $options
     * @return $this
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = 'add-products-mass';
        }

        if (empty($options['handler'])) {
            $options['handler'] = 'orob2b_shopping_list.mass_action.add_products_handler';
        }

        if (empty($options['route'])) {
            $options['route'] = 'orob2b_shopping_list_add_products_massaction';
        }

        if (empty($options['route_parameters'])) {
            $options['route_parameters'] = [];
        }

        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajax';
        }

        $options['confirmation'] = false;

        return parent::setOptions($options);
    }
}