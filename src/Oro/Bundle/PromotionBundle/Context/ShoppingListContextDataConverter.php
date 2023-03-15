<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Discount\Converter\LineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

/**
 * Data converter that prepares promotion context data based on shopping list entity to filter applicable promotions.
 */
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
     * @var ShoppingListTotalManager
     */
    private $shoppingListTotalManager;

    public function __construct(
        CriteriaDataProvider $criteriaDataProvider,
        LineItemsToDiscountLineItemsConverter $lineItemsConverter,
        UserCurrencyManager $userCurrencyManager,
        ScopeManager $scopeManager,
        ShoppingListTotalManager $shoppingListTotalManager
    ) {
        $this->criteriaDataProvider = $criteriaDataProvider;
        $this->lineItemsConverter = $lineItemsConverter;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->scopeManager = $scopeManager;
        $this->shoppingListTotalManager = $shoppingListTotalManager;
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

        // There may be cases when entities do not exist yet, so we ignore them.
        $scopeContext = [
            'customer' => $customer?->getId() ? $customer : null,
            'customerGroup' => $customerGroup?->getId() ? $customerGroup : null,
            'website' => $this->criteriaDataProvider->getWebsite($entity)
        ];

        $currency = $this->userCurrencyManager->getUserCurrency();

        return [
            self::CUSTOMER_USER => $customerUser,
            self::CUSTOMER => $customer,
            self::CUSTOMER_GROUP => $customerGroup,
            self::LINE_ITEMS => $this->getLineItems($entity),
            self::SUBTOTAL => $this->getSubtotalAmount($entity, $currency),
            self::CURRENCY => $currency,
            self::CRITERIA => $this->scopeManager->getCriteria('promotion', $scopeContext),
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

    private function getSubtotalAmount(ShoppingList $sourceEntity, string $currency): float
    {
        return $this->shoppingListTotalManager
            ->getShoppingListTotalForCurrency($sourceEntity, $currency)
            ->getSubtotal()
            ->getAmount();
    }
}
