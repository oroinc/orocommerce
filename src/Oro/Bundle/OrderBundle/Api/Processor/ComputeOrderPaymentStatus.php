<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "paymentStatus" field for Order entity.
 */
class ComputeOrderPaymentStatus implements ProcessorInterface
{
    private const FIELD_NAME = 'paymentStatus';

    private DoctrineHelper $doctrineHelper;
    private PaymentStatusLabelFormatter $paymentStatusLabelFormatter;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        PaymentStatusLabelFormatter $paymentStatusLabelFormatter
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentStatusLabelFormatter = $paymentStatusLabelFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequestedForCollection(self::FIELD_NAME, $data)) {
            return;
        }

        $orderIdFieldName = $context->getResultFieldName('id');
        if ($orderIdFieldName) {
            $context->setData($this->applyPaymentStatus($context, $data, $orderIdFieldName));
        }
    }

    private function applyPaymentStatus(
        CustomizeLoadedDataContext $context,
        array $data,
        string $orderIdFieldName
    ): array {
        $ordersIds = $context->getIdentifierValues($data, $orderIdFieldName);
        $statuses = $this->loadPaymentStatuses($ordersIds);
        foreach ($data as $key => $item) {
            $orderId = $item[$orderIdFieldName];
            if ($context->isFieldRequested(self::FIELD_NAME, $item)) {
                $status = null;
                if (isset($statuses[$orderId])) {
                    $code = $statuses[$orderId];
                    $status = [
                        'code'  => $code,
                        'label' => $this->paymentStatusLabelFormatter->formatPaymentStatusLabel($code)
                    ];
                }
                $data[$key][self::FIELD_NAME] = $status;
            }
        }

        return $data;
    }

    /**
     * @param int[] $ordersIds
     *
     * @return array [order id => payment status, ...]
     */
    private function loadPaymentStatuses(array $ordersIds): array
    {
        $qb = $this->doctrineHelper
            ->createQueryBuilder(PaymentStatus::class, 'ps')
            ->select('ps.entityIdentifier, ps.paymentStatus')
            ->where('ps.entityIdentifier IN (:orderIds) AND ps.entityClass = :orderClass')
            ->setParameter('orderIds', $ordersIds)
            ->setParameter('orderClass', Order::class);

        $result = [];
        $rows = $qb->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $result[$row['entityIdentifier']] = $row['paymentStatus'];
        }

        return $result;
    }
}
