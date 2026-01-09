<?php

namespace Oro\Bundle\PaymentBundle\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction;

/**
 * Datagrid action for deleting payment-related records.
 *
 * This action extends the base datagrid action to provide payment-specific deletion
 * functionality with automatic confirmation dialog support.
 */
class PaymentDeleteAction extends AbstractAction
{
    /**
     * @var array
     */
    protected $requiredOptions = ['link'];

    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (!isset($options['confirmation'])) {
            $options['confirmation'] = true;
        }

        return parent::setOptions($options);
    }
}
