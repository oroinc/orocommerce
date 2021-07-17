<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Form\FormValidationHandler;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Calculates totals, subtotals and discounts for orders and their line items.
 * It is expected that MoveSharedDataToContext processor is executed before this processor.
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\MoveSharedDataToContext
 */
class UpdateOrderTotals implements ProcessorInterface
{
    private const ORDERS = 'orders_to_update_totals';

    /** @var TotalHelper */
    private $totalHelper;

    /** @var FormValidationHandler */
    private $validator;

    public function __construct(TotalHelper $totalHelper, FormValidationHandler $validator)
    {
        $this->totalHelper = $totalHelper;
        $this->validator = $validator;
    }

    /**
     * Adds the given order to the list of orders that require the totals update.
     * This list is is stored in shared data.
     */
    public static function addOrderToUpdateTotals(
        ParameterBagInterface $sharedData,
        Order $order,
        FormInterface $form,
        string $orderFieldName = null
    ): void {
        $orderKey = $order->getId();
        if (null === $orderKey) {
            $orderKey = spl_object_hash($order);
        }

        $orders = $sharedData->get(self::ORDERS) ?? [];
        $orders[$orderKey] = [$order, $form, $orderFieldName];
        $sharedData->set(self::ORDERS, $orders);
    }

    /**
     * Moves orders that require the totals update from shared data to the given context.
     */
    public static function moveOrdersRequireTotalsUpdateToContext(CustomizeFormDataContext $context): void
    {
        $sharedData = $context->getSharedData();
        if ($sharedData->has(self::ORDERS)) {
            $context->set(self::ORDERS, $sharedData->get(self::ORDERS));
            $sharedData->remove(self::ORDERS);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->isPrimaryEntityRequest()) {
            return;
        }

        $orders = $context->get(self::ORDERS);
        foreach ($orders as [$order, $form, $orderFieldName]) {
            $this->totalHelper->fill($order);
            $this->validator->validate($form);
            if ($orderFieldName) {
                FormUtil::ensureFieldSubmitted($form, $orderFieldName);
            }
        }
        $context->remove(self::ORDERS);
    }
}
