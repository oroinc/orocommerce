<?php

namespace Oro\Bundle\PromotionBundle\Context;

interface ContextDataConverterInterface
{
    const BILLING_ADDRESS = 'billingAddress';
    const CURRENCY = 'currency';
    const SHIPPING_ADDRESS = 'shippingAddress';
    const CUSTOMER_USER = 'customerUser';
    const CUSTOMER = 'customer';
    const CUSTOMER_GROUP = 'customerGroup';
    const LINE_ITEMS = 'lineItems';
    const SUBTOTAL = 'subtotal';
    const SHIPPING_COST = 'shippingCost';
    const PAYMENT_METHOD = 'paymentMethod';
    const SHIPPING_METHOD = 'shippingMethod';
    const CRITERIA = 'criteria';

    /**
     * @param object $entity
     * @return array
     */
    public function getContextData($entity): array;

    /**
     * @param object $entity
     * @return bool
     */
    public function supports($entity): bool;
}
