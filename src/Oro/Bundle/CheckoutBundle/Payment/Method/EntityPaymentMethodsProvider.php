<?php

namespace Oro\Bundle\CheckoutBundle\Payment\Method;

use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

/**
 * Get payment methods associated with an entity.
 */
class EntityPaymentMethodsProvider
{
    /**
     * @var PaymentTransactionProvider
     */
    private $paymentTransactionProvider;

    /**
     * @var array|string[]
     */
    private $paymentMethodToEntity = [];

    public function __construct(PaymentTransactionProvider $paymentTransactionProvider)
    {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * @param object         $entity
     * @param array|string[] $paymentMethods
     */
    public function storePaymentMethodsToEntity($entity, array $paymentMethods): void
    {
        $this->paymentMethodToEntity[spl_object_hash($entity)] = $paymentMethods;
    }

    /**
     * @param object $object
     * @return array|string[]
     */
    public function getPaymentMethods($object): array
    {
        $methods = $this->paymentTransactionProvider->getPaymentMethods($object);
        if ($methods) {
            return $methods;
        }

        return $this->paymentMethodToEntity[spl_object_hash($object)] ?? [];
    }
}
