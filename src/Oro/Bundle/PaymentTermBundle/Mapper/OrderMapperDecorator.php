<?php

namespace Oro\Bundle\PaymentTermBundle\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

/**
 * Assigns payment term to the order if payment method is payment_term and payment term is provided.
 */
class OrderMapperDecorator implements MapperInterface
{
    public function __construct(
        private MapperInterface $orderMapper,
        private PaymentTermAssociationProvider $paymentTermAssociationProvider,
        private ?string $paymentTermPrefix = null,
    ) {
    }

    #[\Override]
    public function map(Checkout $checkout, array $data = [], array $skipped = [])
    {
        $order = $this->orderMapper->map($checkout, $data, $skipped);

        // Check if payment term is provided in data
        if (!isset($data['paymentTerm']) || !$data['paymentTerm'] instanceof PaymentTerm) {
            return $order;
        }

        // Check if payment method prefix matches payment term prefix
        if ($this->isPaymentTermMethod($checkout)) {
            $this->paymentTermAssociationProvider->setPaymentTerm($order, $data['paymentTerm']);
        }

        return $order;
    }

    private function isPaymentTermMethod(Checkout $checkout): bool
    {
        $paymentMethod = $checkout->getPaymentMethod();

        if (!$paymentMethod || $this->paymentTermPrefix === null) {
            return false;
        }

        [$prefix,] = PrefixedIntegrationIdentifierGenerator::parseIdentifier($paymentMethod);

        return $prefix === $this->paymentTermPrefix;
    }
}
