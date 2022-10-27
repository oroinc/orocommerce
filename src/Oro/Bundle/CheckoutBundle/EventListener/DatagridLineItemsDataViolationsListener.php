<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataViolationsListener as BaseListener;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Adds line items errors data.
 */
class DatagridLineItemsDataViolationsListener extends BaseListener
{
    /** @var CheckoutWorkflowHelper */
    private $checkoutWorkflowHelper;

    public function __construct(
        LineItemViolationsProvider $violationsProvider,
        CheckoutWorkflowHelper $checkoutWorkflowHelper
    ) {
        parent::__construct($violationsProvider);

        $this->checkoutWorkflowHelper = $checkoutWorkflowHelper;
    }

    protected function getAdditionalContext(DatagridLineItemsDataEvent $event): ?WorkflowItem
    {
        $datagrid = $event->getDatagrid();

        $checkoutId = (int) $datagrid->getParameters()
            ->get('checkout_id');
        if (!$checkoutId) {
            return null;
        }

        /** @var OrmDatasource $source */
        $source = $datagrid->getDatasource();

        $checkout = $source->getQueryBuilder()
            ->getEntityManager()
            ->getRepository(Checkout::class)
            ->find($checkoutId);

        if (!$checkout) {
            return null;
        }

        return $this->checkoutWorkflowHelper->getWorkflowItem($checkout);
    }
}
