<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

/**
 * Data converter that prepares discount context based on shopping list entity to calculate discounts.
 */
class ShoppingListDiscountContextConverter implements DiscountContextConverterInterface
{
    /**
     * @var LineItemsToDiscountLineItemsConverter
     */
    protected $lineItemsConverter;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var LineItemNotPricedSubtotalProvider
     */
    protected $lineItemNotPricedSubtotalProvider;

    /** @var ShoppingListTotalManager|null */
    private $shoppingListTotalManager;

    /**
     * @param LineItemsToDiscountLineItemsConverter $lineItemsConverter
     * @param UserCurrencyManager $currencyManager
     * @param LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider
     */
    public function __construct(
        LineItemsToDiscountLineItemsConverter $lineItemsConverter,
        UserCurrencyManager $currencyManager,
        LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider
    ) {
        $this->lineItemsConverter = $lineItemsConverter;
        $this->currencyManager = $currencyManager;
        $this->lineItemNotPricedSubtotalProvider = $lineItemNotPricedSubtotalProvider;
    }

    /**
     * @param ShoppingListTotalManager|null $shoppingListTotalManager
     */
    public function setShoppingListTotalManager(?ShoppingListTotalManager $shoppingListTotalManager): void
    {
        $this->shoppingListTotalManager = $shoppingListTotalManager;
    }

    /**
     * @param ShoppingList $sourceEntity
     * {@inheritdoc}
     */
    public function convert($sourceEntity): DiscountContext
    {
        if (!$this->supports($sourceEntity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($sourceEntity))
            );
        }

        $discountContext = new DiscountContext();
        $discountContext->setSubtotal($this->getSubtotalAmount($sourceEntity));

        $discountLineItems = $this->lineItemsConverter->convert($sourceEntity->getLineItems()->toArray());
        $discountContext->setLineItems($discountLineItems);

        return $discountContext;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($sourceEntity): bool
    {
        return $sourceEntity instanceof ShoppingList;
    }

    /**
     * @param ShoppingList $sourceEntity
     *
     * @return float
     */
    private function getSubtotalAmount(ShoppingList $sourceEntity): float
    {
        $currency = $this->currencyManager->getUserCurrency();
        if ($this->shoppingListTotalManager) {
            $subtotal = $this->shoppingListTotalManager
                ->getShoppingListTotalForCurrency($sourceEntity, $currency)
                ->getSubtotal();
        } else {
            $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($sourceEntity, $currency);
        }

        return $subtotal->getAmount();
    }
}
