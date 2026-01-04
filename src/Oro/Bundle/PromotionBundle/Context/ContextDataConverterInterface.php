<?php

namespace Oro\Bundle\PromotionBundle\Context;

/**
 * Promotion Context Data Provider Interface. Context is used for promotion expression.
 */
interface ContextDataConverterInterface
{
    public const BILLING_ADDRESS = 'billingAddress';
    public const CURRENCY = 'currency';
    public const SHIPPING_ADDRESS = 'shippingAddress';
    public const CUSTOMER_USER = 'customerUser';
    public const CUSTOMER = 'customer';
    public const CUSTOMER_GROUP = 'customerGroup';
    public const LINE_ITEMS = 'lineItems';
    public const SUBTOTAL = 'subtotal';
    public const SHIPPING_COST = 'shippingCost';
    public const PAYMENT_METHOD = 'paymentMethod';
    public const PAYMENT_METHODS = 'paymentMethods';
    public const SHIPPING_METHOD = 'shippingMethod';
    public const SHIPPING_METHOD_TYPE = 'shippingMethodType';
    public const CRITERIA = 'criteria';
    public const APPLIED_COUPONS = 'appliedCoupons';

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
