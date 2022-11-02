<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
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
     * @var ShoppingListTotalManager
     */
    private $shoppingListTotalManager;

    public function __construct(
        LineItemsToDiscountLineItemsConverter $lineItemsConverter,
        UserCurrencyManager $currencyManager,
        ShoppingListTotalManager $shoppingListTotalManager
    ) {
        $this->lineItemsConverter = $lineItemsConverter;
        $this->currencyManager = $currencyManager;
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

    private function getSubtotalAmount(ShoppingList $sourceEntity): float
    {
        $currency = $this->currencyManager->getUserCurrency();
        return $this->shoppingListTotalManager
            ->getShoppingListTotalForCurrency($sourceEntity, $currency)
            ->getSubtotal()
            ->getAmount();
    }
}
