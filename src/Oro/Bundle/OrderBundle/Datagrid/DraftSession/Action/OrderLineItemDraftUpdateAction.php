<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction;

/**
 * Datagrid action to update order line items in the order draft edit mode.
 */
class OrderLineItemDraftUpdateAction extends AbstractAction
{
    protected $requiredOptions = ['link'];

    protected static array $defaultOptions = [
        'launcherOptions' => [
            'onClickReturnValue' => true,
            'runAction' => true,
            'className' => 'no-hash',
            'widget' => [],
            'messages' => [],
        ],
    ];

    #[\Override]
    public function getOptions(): ActionConfiguration
    {
        $options = parent::getOptions();
        $finalOptions = array_replace_recursive(self::$defaultOptions, $options->toArray());
        $options->merge($finalOptions);

        return $options;
    }
}
