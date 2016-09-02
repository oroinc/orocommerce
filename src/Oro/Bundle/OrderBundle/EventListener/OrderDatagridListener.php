<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\GridResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;

class OrderDatagridListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param GridResultAfter $event
     */
    public function onResultAfter(GridResultAfter $event)
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
