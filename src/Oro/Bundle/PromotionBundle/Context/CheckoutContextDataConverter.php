<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalAmountProvider;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderLineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutContextDataConverter implements ContextDataConverterInterface
{
    /**
     * @var CustomerUserRelationsProvider
     */
    protected $relationsProvider;

    /**
     * @var OrderLineItemsToDiscountLineItemsConverter
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
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var CheckoutSubtotalAmountProvider
     */
    protected $checkoutSubtotalAmountProvider;

    /**
     * @param CustomerUserRelationsProvider $relationsProvider
     * @param OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter
     * @param UserCurrencyManager $userCurrencyManager
     * @param ScopeManager $scopeManager
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param CheckoutSubtotalAmountProvider $checkoutSubtotalAmountProvider
     */
    public function __construct(
        CustomerUserRelationsProvider $relationsProvider,
        OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter,
        UserCurrencyManager $userCurrencyManager,
        ScopeManager $scopeManager,
        CheckoutLineItemsManager $checkoutLineItemsManager,
        CheckoutSubtotalAmountProvider $checkoutSubtotalAmountProvider
    ) {
        $this->relationsProvider = $relationsProvider;
        $this->lineItemsConverter = $lineItemsConverter;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->scopeManager = $scopeManager;
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->checkoutSubtotalAmountProvider = $checkoutSubtotalAmountProvider;
    }

    /**
     * @param Checkout $entity
     * {@inheritdoc}
     */
    public function getContextData($entity): array
    {
        if (!$this->supports($entity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($entity))
            );
        }

        $customerUser = $entity->getCustomerUser();
        $customer = $this->relationsProvider->getCustomer($customerUser);
        $customerGroup = $this->relationsProvider->getCustomerGroup($customerUser);
        if (!$customerGroup && $entity->getCustomer()) {
            $customerGroup = $entity->getCustomer()->getGroup();
        }

        return [
            self::CUSTOMER_USER => $entity->getCustomerUser(),
            self::CUSTOMER => $customer ?: $entity->getCustomer(),
            self::CUSTOMER_GROUP => $customerGroup,
            self::LINE_ITEMS => $this->getLineItems($entity),
            self::SUBTOTAL => $this->checkoutSubtotalAmountProvider->getSubtotalAmount($entity),
            self::CURRENCY => $this->userCurrencyManager->getUserCurrency(),
            self::CRITERIA => $this->scopeManager->getCriteria('promotion'),
            self::BILLING_ADDRESS => $entity->getBillingAddress(),
            self::SHIPPING_ADDRESS => $entity->getShippingAddress(),
            self::SHIPPING_COST => $entity->getShippingCost(),
            self::PAYMENT_METHOD => $entity->getPaymentMethod(),
            self::SHIPPING_METHOD => $entity->getShippingMethod(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof Checkout && $entity->getSourceEntity() instanceof ShoppingList;
    }

    /**
     * @param Checkout $entity
     * @return DiscountLineItem[]
     */
    protected function getLineItems(Checkout $entity)
    {
        return $this->lineItemsConverter->convert(
            $this->checkoutLineItemsManager->getData($entity)->toArray()
        );
    }
}
