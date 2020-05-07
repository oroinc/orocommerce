<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

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

        $subtotal = $sourceEntity->getSubtotal();
        if (!$subtotal || !$subtotal->getAmount()) {
            $currency = $this->currencyManager->getUserCurrency();
            $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($sourceEntity, $currency);
        }

        $discountContext = new DiscountContext();
        $discountContext->setSubtotal($subtotal->getAmount());

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
}
