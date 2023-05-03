<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderLineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProviderInterface;
use Oro\Bundle\PromotionBundle\ValidationService\CouponValidationService;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

/**
 * Data converter that prepares promotion context data based on order entity to filter applicable promotions.
 */
class OrderContextDataConverter implements ContextDataConverterInterface
{
    public const SUB_ORDERS = 'subOrders';

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
    protected $couponValidationService;

    /**
     * @var EntityCouponsProviderInterface
     */
    protected $entityCouponsProvider;

    /**
     * @var EntityPaymentMethodsProvider
     */
    protected $paymentMethodsProvider;

    public function __construct(
        CriteriaDataProvider $criteriaDataProvider,
        OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter,
        ScopeManager $scopeManager,
        SubtotalProviderInterface $lineItemSubtotalProvider,
        CouponValidationService $couponValidationService,
        EntityCouponsProviderInterface $entityCouponsProvider,
        EntityPaymentMethodsProvider $paymentMethodsProvider
    ) {
        $this->criteriaDataProvider = $criteriaDataProvider;
        $this->lineItemsConverter = $lineItemsConverter;
        $this->scopeManager = $scopeManager;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
        $this->couponValidationService = $couponValidationService;
        $this->entityCouponsProvider = $entityCouponsProvider;
        $this->paymentMethodsProvider = $paymentMethodsProvider;
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

        // There may be cases when entities do not exist yet, so we ignore them.
        $scopeContext = [
            'customer' => $customer?->getId() ? $customer : null,
            'customerGroup' => $customerGroup?->getId() ? $customerGroup : null,
            'website' => $this->criteriaDataProvider->getWebsite($entity)
        ];

        $subtotal = $this->lineItemSubtotalProvider->getSubtotal($entity);
        $paymentMethods = $this->paymentMethodsProvider->getPaymentMethods($entity);

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
            self::APPLIED_COUPONS => $this->getValidateCoupons($this->entityCouponsProvider->getCoupons($entity)),
            self::PAYMENT_METHOD => reset($paymentMethods),
            self::PAYMENT_METHODS => $paymentMethods,
            self::SUB_ORDERS => $entity->getSubOrders()->toArray(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof Order && $entity->getSourceEntityClass() !== Quote::class;
    }

    /**
     * @param Order $entity
     * @return DiscountLineItem[]
     */
    private function getLineItems(Order $entity)
    {
        return $this->lineItemsConverter->convert(
            $entity->getLineItems()->toArray()
        );
    }

    /**
     * @param Collection|Coupon[] $coupons
     * @return Collection
     */
    private function getValidateCoupons(Collection $coupons): Collection
    {
        return $coupons->filter(
            function (Coupon $coupon) {
                return $this->couponValidationService->isValid($coupon);
            }
        );
    }
}
