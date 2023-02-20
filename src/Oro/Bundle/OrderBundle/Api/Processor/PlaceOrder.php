<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Create\SaveEntity;
use Oro\Bundle\ApiBundle\Util\ActionGroupExecutor;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves a new order to the database and purchases it if a payment method is set to the payment options.
 */
class PlaceOrder implements ProcessorInterface
{
    private ActionGroupExecutor $actionGroupExecutor;
    private DoctrineHelper $doctrineHelper;
    private string $orderPurchaseActionGroupName;

    public function __construct(
        ActionGroupExecutor $actionGroupExecutor,
        DoctrineHelper $doctrineHelper,
        string $orderPurchaseActionGroupName
    ) {
        $this->actionGroupExecutor = $actionGroupExecutor;
        $this->doctrineHelper = $doctrineHelper;
        $this->orderPurchaseActionGroupName = $orderPurchaseActionGroupName;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if ($context->isProcessed(SaveEntity::OPERATION_NAME)) {
            // an order was already placed
            return;
        }

        $order = $context->getResult();
        if (!\is_object($order)) {
            // an order does not exist
            return;
        }

        $this->processOrder($order, $context);
        $context->setProcessed(SaveEntity::OPERATION_NAME);
    }

    private function processOrder(Order $order, CreateContext $context): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManager($order, false);
        $em->getConnection()->beginTransaction();
        try {
            if ($this->placeOrder($order, $em, $context)) {
                $em->getConnection()->commit();
            } else {
                $em->getConnection()->rollBack();
            }
        } catch (\Throwable $e) {
            $em->getConnection()->rollBack();

            throw $e;
        }
    }

    private function placeOrder(Order $order, EntityManagerInterface $em, CreateContext $context): bool
    {
        $em->persist($order);
        $em->flush();

        if (!$this->purchaseOrder($order, $context)) {
            return false;
        }

        $context->setId($context->getMetadata()->getIdentifierValue($order));

        return true;
    }

    private function purchaseOrder(Order $order, CreateContext $context): bool
    {
        $paymentOptions = PaymentOptionsContextUtil::all($context->getSharedData(), $order);
        if (null === $paymentOptions || !$paymentOptions->has(PaymentOptionsContextUtil::PAYMENT_METHOD)) {
            return true;
        }

        return $this->actionGroupExecutor->execute(
            $this->orderPurchaseActionGroupName,
            $paymentOptions,
            $context,
            'purchase order error'
        );
    }
}
