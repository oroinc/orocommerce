<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PromotionBundle\Discount\Converter\LineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListContextDataConverter implements ContextDataConverterInterface
{
    /**
     * @var CustomerUserRelationsProvider
     */
    protected $relationsProvider;

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
     * @param CustomerUserRelationsProvider $relationsProvider
     * @param LineItemsToDiscountLineItemsConverter $lineItemsConverter
     * @param UserCurrencyManager $userCurrencyManager
     * @param ScopeManager $scopeManager
     * @param LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider
     */
    public function __construct(
        CustomerUserRelationsProvider $relationsProvider,
        LineItemsToDiscountLineItemsConverter $lineItemsConverter,
        UserCurrencyManager $userCurrencyManager,
        ScopeManager $scopeManager,
        LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider
    ) {
        $this->relationsProvider = $relationsProvider;
        $this->lineItemsConverter = $lineItemsConverter;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->scopeManager = $scopeManager;
        $this->lineItemNotPricedSubtotalProvider = $lineItemNotPricedSubtotalProvider;
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

        $customerUser = $entity->getCustomerUser();
        $customer = $this->relationsProvider->getCustomer($customerUser);
        $customerGroup = $this->relationsProvider->getCustomerGroup($customerUser);
        if (!$customerGroup && $entity->getCustomer()) {
            $customerGroup = $entity->getCustomer()->getGroup();
        }
        $currency = $this->userCurrencyManager->getUserCurrency();
        $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($entity, $currency);

        return [
            self::CUSTOMER_USER => $entity->getCustomerUser(),
            self::CUSTOMER => $customer ?: $entity->getCustomer(),
            self::CUSTOMER_GROUP => $customerGroup,
            self::LINE_ITEMS => $this->getLineItems($entity),
            self::SUBTOTAL => $subtotal->getAmount(),
            self::CURRENCY => $currency,
            self::CRITERIA => $this->scopeManager->getCriteria('promotion'),
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
}
