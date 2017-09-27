<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PromotionBundle\Discount\Converter\LineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProviderInterface;
use Oro\Bundle\PromotionBundle\ValidationService\CouponValidationService;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListContextDataConverter implements ContextDataConverterInterface
{
    /**
     * @var CriteriaDataProvider
     */
    protected $criteriaDataProvider;

    /**
     * @var LineItemsToDiscountLineItemsConverter
     */
    protected $lineItemsConverter;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var LineItemNotPricedSubtotalProvider
     */
    protected $lineItemNotPricedSubtotalProvider;

    /**
     * @var CouponValidationService
     */
    protected $couponValidationService;

    /**
     * @var EntityCouponsProviderInterface
     */
    protected $entityCouponsProvider;

    /**
     * @param CriteriaDataProvider $criteriaDataProvider
     * @param LineItemsToDiscountLineItemsConverter $lineItemsConverter
     * @param UserCurrencyManager $userCurrencyManager
     * @param ScopeManager $scopeManager
     * @param LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider
     * @param CouponValidationService $couponValidationService
     * @param EntityCouponsProviderInterface $entityCouponsProvider
     */
    public function __construct(
        CriteriaDataProvider $criteriaDataProvider,
        LineItemsToDiscountLineItemsConverter $lineItemsConverter,
        UserCurrencyManager $userCurrencyManager,
        ScopeManager $scopeManager,
        LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider,
        CouponValidationService $couponValidationService,
        EntityCouponsProviderInterface $entityCouponsProvider
    ) {
        $this->criteriaDataProvider = $criteriaDataProvider;
        $this->lineItemsConverter = $lineItemsConverter;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->scopeManager = $scopeManager;
        $this->lineItemNotPricedSubtotalProvider = $lineItemNotPricedSubtotalProvider;
        $this->couponValidationService = $couponValidationService;
        $this->entityCouponsProvider = $entityCouponsProvider;
    }

    /**
     * @param ShoppingList $entity
     * {@inheritdoc}
     */
    public function getContextData($entity): array
    {
        if (!$this->supports($entity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Entity "%s" is not supported.', get_class($entity))
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

        $currency = $this->userCurrencyManager->getUserCurrency();
        $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($entity, $currency);

        return [
            self::CUSTOMER_USER => $customerUser,
            self::CUSTOMER => $customer,
            self::CUSTOMER_GROUP => $customerGroup,
            self::LINE_ITEMS => $this->getLineItems($entity),
            self::SUBTOTAL => $subtotal->getAmount(),
            self::CURRENCY => $currency,
            self::CRITERIA => $this->scopeManager->getCriteria('promotion', $scopeContext),
            self::APPLIED_COUPONS => $this->getValidateCoupons($this->entityCouponsProvider->getCoupons($entity))
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof ShoppingList;
    }

    /**
     * @param ShoppingList $entity
     * @return DiscountLineItem[]|array
     */
    private function getLineItems(ShoppingList $entity)
    {
        return $this->lineItemsConverter->convert($entity->getLineItems()->toArray());
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
