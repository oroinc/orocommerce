<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderLineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\PromotionBundle\ValidationService\CouponValidationService;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class OrderContextDataConverter implements ContextDataConverterInterface
{
    /**
     * @var CriteriaDataProvider
     */
    protected $criteriaDataProvider;

    /**
     * @var OrderLineItemsToDiscountLineItemsConverter
     */
    protected $lineItemsConverter;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var SubtotalProviderInterface
     */
    protected $lineItemSubtotalProvider;

    /**
     * @var CouponValidationService
     */
    private $couponValidationService;

    /**
     * @param CriteriaDataProvider $criteriaDataProvider
     * @param OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter
     * @param ScopeManager $scopeManager
     * @param SubtotalProviderInterface $lineItemSubtotalProvider
     * @param CouponValidationService $couponValidationService
     */
    public function __construct(
        CriteriaDataProvider $criteriaDataProvider,
        OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter,
        ScopeManager $scopeManager,
        SubtotalProviderInterface $lineItemSubtotalProvider,
        CouponValidationService $couponValidationService
    ) {
        $this->criteriaDataProvider = $criteriaDataProvider;
        $this->lineItemsConverter = $lineItemsConverter;
        $this->scopeManager = $scopeManager;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
        $this->couponValidationService = $couponValidationService;
    }

    /**
     * @param Order $entity
     * {@inheritdoc}
     */
    public function getContextData($entity): array
    {
        if (!$this->supports($entity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($entity))
            );
        }

        $customerUser = $this->criteriaDataProvider->getCustomerUser($entity);
        $customer = $this->criteriaDataProvider->getCustomer($entity);
        $customerGroup = $this->criteriaDataProvider->getCustomerGroup($entity);

        $scopeContext = [
            'customer' => $customer,
            'customerGroup' => $customerGroup,
            'website' => $this->criteriaDataProvider->getWebsite($entity)
        ];

        $subtotal = $this->lineItemSubtotalProvider->getSubtotal($entity);

        return [
            self::CUSTOMER_USER => $customerUser,
            self::CUSTOMER => $customer,
            self::CUSTOMER_GROUP => $customerGroup,
            self::LINE_ITEMS => $this->getLineItems($entity),
            self::SUBTOTAL => $subtotal->getAmount(),
            self::CURRENCY => $entity->getCurrency(),
            self::CRITERIA => $this->scopeManager->getCriteria('promotion', $scopeContext),
            self::BILLING_ADDRESS => $entity->getBillingAddress(),
            self::SHIPPING_ADDRESS => $entity->getShippingAddress(),
            self::SHIPPING_COST => $entity->getShippingCost(),
            self::SHIPPING_METHOD => $entity->getShippingMethod(),
            self::SHIPPING_METHOD_TYPE => $entity->getShippingMethodType(),
            self::APPLIED_COUPONS => $this->getValidateCoupons($entity->getAppliedCoupons()->toArray())
        ];
    }

    /**
     * @param array $coupons
     * @return array
     */
    private function getValidateCoupons(array $coupons)
    {
        return  array_filter($coupons, function ($coupon) {
            return $this->couponValidationService->isValid($coupon);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof Order;
    }

    /**
     * @param Order $entity
     * @return DiscountLineItem[]
     */
    protected function getLineItems(Order $entity)
    {
        return $this->lineItemsConverter->convert(
            $entity->getLineItems()->toArray()
        );
    }
}
