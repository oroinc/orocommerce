<?php

namespace OroB2B\Bundle\PaymentBundle\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction;

class PaymentDeleteAction extends AbstractAction
{
    /**
     * @var array
     */
    protected $requiredOptions = ['link'];

    /**
     * @param ActionConfiguration $options
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (!isset($options['confirmation'])) {
            $options['confirmation'] = true;
        }

        parent::setOptions($options);
    }
}
