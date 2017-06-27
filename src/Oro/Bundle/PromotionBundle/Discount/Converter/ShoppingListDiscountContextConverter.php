<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

class ShoppingListDiscountContextConverter implements DiscountContextConverterInterface
{
    /**
     * @var ShoppingListTotalManager
     */
    protected $shoppingListTotalManager;

    /**
     * @var LineItemsToDiscountLineItemsConverter
     */
    protected $lineItemsConverter;

    /**
     * @param ShoppingListTotalManager $shoppingListTotalManager
     * @param LineItemsToDiscountLineItemsConverter $lineItemsConverter
     */
    public function __construct(
        ShoppingListTotalManager $shoppingListTotalManager,
        LineItemsToDiscountLineItemsConverter $lineItemsConverter
    ) {
        $this->shoppingListTotalManager = $shoppingListTotalManager;
        $this->lineItemsConverter = $lineItemsConverter;
    }

    /**
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

        $this->shoppingListTotalManager->setSubtotals([$sourceEntity], false);
        $discountContext->setSubtotal($sourceEntity->getSubtotal()->getAmount());

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
