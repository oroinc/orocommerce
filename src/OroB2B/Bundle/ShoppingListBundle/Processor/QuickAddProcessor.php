<?php

namespace OroB2B\Bundle\ShoppingListBundle\Processor;

use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface;
use OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Symfony\Component\HttpFoundation\Request;

class QuickAddProcessor implements ComponentProcessorInterface
{
    const NAME = 'orob2b_shopping_list_quick_add_processor';

    /** @var ShoppingListLineItemHandler */
    protected $shoppingListLineItemHandler;

    /**
     * @param ShoppingListLineItemHandler $shoppingListLineItemHandler
     */
    public function __construct(ShoppingListLineItemHandler $shoppingListLineItemHandler)
    {
        $this->shoppingListLineItemHandler = $shoppingListLineItemHandler;
    }

    /** {@inheritdoc} */
    public function process(array $data, Request $request)
    {
        if (!array_key_exists('shoppingList', $data) || !array_key_exists('productIds', $data)) {
            return;
        }

        $shoppingListId = (int)$data['shoppingList'];
        $productIds = $data['productIds'];

        if (!$shoppingListId || !is_array($productIds) || !$productIds) {
            return;
        }

        $shoppingList = $this->shoppingListLineItemHandler->getShoppingList($shoppingListId);
        if (!$shoppingList) {
            return;
        }

        $this->shoppingListLineItemHandler->createForShoppingList($shoppingList, $productIds);
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }
}
