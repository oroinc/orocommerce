<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "paymentMethod" field for Order entity.
 */
class ComputeOrderPaymentMethod implements ProcessorInterface
{
    private const FIELD_NAME = 'paymentMethod';

    private DoctrineHelper $doctrineHelper;
    private PaymentMethodLabelFormatter $paymentMethodFormatter;

    public function __construct(DoctrineHelper $doctrineHelper, PaymentMethodLabelFormatter $paymentMethodFormatter)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentMethodFormatter = $paymentMethodFormatter;
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
            $context->setData($this->applyPaymentMethod($context, $data, $orderIdFieldName));
        }
    }

    private function applyPaymentMethod(
        CustomizeLoadedDataContext $context,
        array $data,
        string $orderIdFieldName
    ): array {
        $ordersIds = $context->getIdentifierValues($data, $orderIdFieldName);
        $methods = $this->loadPaymentMethods($ordersIds);
        foreach ($data as $key => $item) {
            $orderId = $item[$orderIdFieldName];
            if ($context->isFieldRequested(self::FIELD_NAME, $item)) {
                $paymentMethods = [];
                if (!empty($methods[$orderId])) {
                    foreach ($methods[$orderId] as $paymentMethod) {
                        $paymentMethods[] = [
                            'code'  => $paymentMethod,
                            'label' => $this->paymentMethodFormatter->formatPaymentMethodLabel($paymentMethod)
                        ];
                    }
                }
                $data[$key][self::FIELD_NAME] = $paymentMethods;
            }
        }

        return $data;
    }

    /**
     * @param int[] $ordersIds
     *
     * @return array [order id => [payment method, ...], ...]
     */
    private function loadPaymentMethods(array $ordersIds): array
    {
        /** @var PaymentTransactionRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository(PaymentTransaction::class);

        return $repo->getPaymentMethods(Order::class, $ordersIds);
    }
}
