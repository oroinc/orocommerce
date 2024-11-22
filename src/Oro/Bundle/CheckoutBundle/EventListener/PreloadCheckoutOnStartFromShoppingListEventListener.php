<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Event\ExtendableConditionEvent;

/**
 * Preloads line items to-one and to-many relations to avoid one-by-one separate queries.
 */
class PreloadCheckoutOnStartFromShoppingListEventListener
{
    private PreloadingManager $preloadingManager;

    private array $fieldsToPreload = [];

    public function __construct(PreloadingManager $preloadingManager)
    {
        $this->preloadingManager = $preloadingManager;
    }

    public function setFieldsToPreload(array $fieldsToPreload): void
    {
        $this->fieldsToPreload = $fieldsToPreload;
    }

    public function onStartFromShoppingList(ExtendableConditionEvent $event): void
    {
        $shoppingList = $event->getData()?->offsetGet('shoppingList');
        if (!$shoppingList instanceof ShoppingList) {
            return;
        }

        $this->preloadingManager->preloadInEntities(
            $shoppingList->getLineItems()?->toArray() ?? [],
            $this->fieldsToPreload
        );
    }
}
