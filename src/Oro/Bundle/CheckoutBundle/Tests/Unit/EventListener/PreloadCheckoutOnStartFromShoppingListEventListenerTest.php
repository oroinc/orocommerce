<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\PreloadCheckoutOnStartFromShoppingListEventListener;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreloadCheckoutOnStartFromShoppingListEventListenerTest extends TestCase
{
    private PreloadingManager|MockObject $preloadingManager;

    private PreloadCheckoutOnStartFromShoppingListEventListener $listener;

    private array $fieldsToPreload = [
        'product' => [
            'backOrder' => [],
            'category' => [
                'backOrder' => [],
                'decrementQuantity' => [],
                'highlightLowInventory' => [],
                'inventoryThreshold' => [],
                'isUpcoming' => [],
                'lowInventoryThreshold' => [],
                'manageInventory' => [],
                'maximumQuantityToOrder' => [],
                'minimumQuantityToOrder' => [],
            ],
            'decrementQuantity' => [],
            'highlightLowInventory' => [],
            'inventoryThreshold' => [],
            'isUpcoming' => [],
            'lowInventoryThreshold' => [],
            'manageInventory' => [],
            'maximumQuantityToOrder' => [],
            'minimumQuantityToOrder' => [],
            'unitPrecisions' => [],
        ],
        'kitItemLineItems' => [
            'kitItem' => [
                'labels' => [],
                'productUnit' => [],
            ],
            'product' => [
                'names' => [],
                'images' => [
                    'image' => [
                        'digitalAsset' => [
                            'titles' => [],
                            'sourceFile' => [
                                'digitalAsset' => [],
                            ],
                        ],
                    ],
                    'types' => [],
                ],
                'unitPrecisions' => [],
            ],
            'unit' => [],
        ],
    ];

    protected function setUp(): void
    {
        $this->preloadingManager = $this->createMock(PreloadingManager::class);

        $this->listener = new PreloadCheckoutOnStartFromShoppingListEventListener($this->preloadingManager);
    }

    public function testOnStartFromShoppingListWhenEntityNotShoppingList(): void
    {
        $context = new ExtendableEventData(['checkout' => new Checkout()]);
        $event = new ExtendableConditionEvent($context);

        $this->preloadingManager
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onStartFromShoppingList($event);
    }

    public function testOnStartFromShoppingList(): void
    {
        $shoppingList = (new ShoppingList())
            ->addLineItem(new LineItem());
        $context = new ExtendableEventData(['checkout' => new Checkout(), 'shoppingList' => $shoppingList]);

        $event = new ExtendableConditionEvent($context);

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with($shoppingList->getLineItems()->toArray(), $this->fieldsToPreload);

        $this->listener->onStartFromShoppingList($event);
    }
}
