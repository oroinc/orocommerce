<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Extension\MassAction\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\AjaxMassAction;

/**
 * Defines default options for getting product ids mass action.
 */
class GetSelectedProductIdsMassAction extends AjaxMassAction
{
    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (!isset($options['frontend_type'])) {
            $options['frontend_type'] = 'get-selected-product-ids-mass';
        }

        if (!isset($options['handler'])) {
            $options['handler'] =
                'oro_product.datagrid.extension.mass_action.get_selected_product_ids_mass_action_handler';
        }

        if (!isset($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajax';
        }

        $options['confirmation'] = false;
        $options['reloadData'] = false;

        return parent::setOptions($options);
    }
}
