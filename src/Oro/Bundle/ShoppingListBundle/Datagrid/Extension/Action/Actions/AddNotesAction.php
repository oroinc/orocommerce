<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction;

/**
 * Class alias for the js datagrid action.
 */
class AddNotesAction extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        $options['confirmation'] = false;

        parent::setOptions($options);
    }
}
