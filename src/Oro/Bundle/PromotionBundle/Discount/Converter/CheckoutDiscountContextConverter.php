<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalAmountProvider;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutDiscountContextConverter implements DiscountContextConverterInterface
{
    /**
     * @var OrderLineItemsToDiscountLineItemsConverter
     */
    protected $lineItemsConverter;

    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var CheckoutSubtotalAmountProvider
     */
    protected $checkoutSubtotalAmountProvider;

    /**
     * @param OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param CheckoutSubtotalAmountProvider $checkoutSubtotalAmountProvider
     */
    public function __construct(
        OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter,
        CheckoutLineItemsManager $checkoutLineItemsManager,
        CheckoutSubtotalAmountProvider $checkoutSubtotalAmountProvider
    ) {
        $this->lineItemsConverter = $lineItemsConverter;
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->checkoutSubtotalAmountProvider = $checkoutSubtotalAmountProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($sourceEntity): DiscountContext
    {
        /** @var Checkout $sourceEntity */
        if (!$this->supports($sourceEntity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($sourceEntity))
            );
        }

        $discountContext = new DiscountContext();

        $subtotal = $this->checkoutSubtotalAmountProvider->getSubtotalAmount($sourceEntity);
        $discountContext->setSubtotal($subtotal);

        $discountLineItems = $this->lineItemsConverter->convert(
            $this->checkoutLineItemsManager->getData($sourceEntity)->toArray()
        );
        $discountContext->setLineItems($discountLineItems);

        return $discountContext;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($sourceEntity): bool
    {
        return $sourceEntity instanceof Checkout && $sourceEntity->getSourceEntity() instanceof ShoppingList;
    }
}
