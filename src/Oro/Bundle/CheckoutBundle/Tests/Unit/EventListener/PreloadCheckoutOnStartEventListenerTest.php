<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\EventListener\PreloadCheckoutOnStartEventListener;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreloadCheckoutOnStartEventListenerTest extends TestCase
{
    private PreloadingManager|MockObject $preloadingManager;

    private PreloadCheckoutOnStartEventListener $listener;

    private array $fieldsToPreload = ['product' =>  []];

    #[\Override]
    protected function setUp(): void
    {
        $this->preloadingManager = $this->createMock(PreloadingManager::class);

        $this->listener = new PreloadCheckoutOnStartEventListener($this->preloadingManager);
        $this->listener->setFieldsToPreload($this->fieldsToPreload);
    }

    public function testOnStartWhenEntityNotCheckout(): void
    {
        $context = (new ActionData())
            ->set('checkout', new \stdClass());
        $event = new ExtendableConditionEvent($context);

        $this->preloadingManager
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onStart($event);
    }

    public function testOnStart(): void
    {
        $shoppingList = new ShoppingList();
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList))
            ->addLineItem((new CheckoutLineItem()));
        $context = (new ActionData())
            ->set('checkout', $checkout);
        $event = new ExtendableConditionEvent($context);

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with($checkout->getLineItems()->toArray(), $this->fieldsToPreload);

        $this->listener->onStart($event);
    }
}
