<?php

namespace Oro\Bundle\ShoppingListBundle\Processor;

use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Handles logic related to quick order process.
 */
class QuickAddProcessor extends AbstractShoppingListQuickAddProcessor
{
    private MessageGenerator $messageGenerator;

    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        ProductMapperInterface $productMapper,
        MessageGenerator $messageGenerator
    ) {
        parent::__construct($shoppingListLineItemHandler, $productMapper);
        $this->messageGenerator = $messageGenerator;
    }

    #[\Override]
    public function process(array $data, Request $request): ?Response
    {
        if (
            empty($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]) ||
            !\is_array($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])
        ) {
            return null;
        }

        $shoppingListId = null;
        if (!empty($data[ProductDataStorage::ADDITIONAL_DATA_KEY])) {
            $shoppingListId = (int)$data[ProductDataStorage::ADDITIONAL_DATA_KEY] ?: null;
        }
        $shoppingList = $this->shoppingListLineItemHandler->getShoppingList($shoppingListId);

        /** @var Session $session */
        $session = $request->getSession();
        $flashBag = $session->getFlashBag();

        $entitiesCount = $this->fillShoppingList($shoppingList, $data);
        if ($entitiesCount) {
            $flashBag->add(
                'success',
                $this->messageGenerator->getSuccessMessage($shoppingList->getId(), $entitiesCount)
            );
        } else {
            $flashBag->add('error', $this->messageGenerator->getFailedMessage());
        }

        return null;
    }
}
