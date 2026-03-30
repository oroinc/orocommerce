<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\DeleteAction;

/**
 * Class alias for the js datagrid action.
 */
class OrderLineItemDraftDeleteAction extends DeleteAction
{
    // No custom logic needed - inherits all from DeleteAction
    /** @var array */
    protected $requiredOptions = [];
}
