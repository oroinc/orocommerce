<?php

namespace Oro\Bundle\PromotionBundle\Context;

/**
 * Promotion Context Data Provider Interface. Context is used for promotion expression.
 */
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
    const PAYMENT_METHODS = 'paymentMethods';
    const SHIPPING_METHOD = 'shippingMethod';
    const SHIPPING_METHOD_TYPE = 'shippingMethodType';
    const CRITERIA = 'criteria';
    const APPLIED_COUPONS = 'appliedCoupons';

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
