<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;

/**
 * Enriches order datagrid records with payment method information.
 *
 * Listens to datagrid result events and augments order records with associated payment methods
 * by querying the payment transaction repository.
 * This allows the datagrid to display payment method information alongside order data.
 */
class OrderDatagridListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        $orderIds = [];
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        foreach ($records as $record) {
            $orderIds[] = $record->getValue('id');
        }

        $repository = $this->doctrineHelper->getEntityRepository(PaymentTransaction::class);
        /** @var PaymentTransactionRepository $repository */
        $methods = $repository->getPaymentMethods(Order::class, $orderIds);
        foreach ($records as $record) {
            $id = $record->getValue('id');
            $paymentMethods = isset($methods[$id]) ? $methods[$id] : [];
            $record->addData(['paymentMethods' => $paymentMethods]);
        }
    }
}
