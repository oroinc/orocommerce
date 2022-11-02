<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\SearchBundle\Event\BeforeEntityAddToIndexEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\ShoppingListBeforeAddToIndexListener;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShoppingListBeforeAddToIndexListenerTest extends TestCase
{
    use EntityTrait;

    /**
     * @var TranslatorInterface|MockObject
     */
    private $translator;

    /**
     * @var ShoppingListBeforeAddToIndexListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new ShoppingListBeforeAddToIndexListener();
    }

    public function testCheckEntityNeedIndexNewGuestShoppingList(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingListStub::class, ['id' => null]);
        $visitor = $this->getEntity(CustomerVisitor::class, ['id' => 123]);
        $shoppingList->addVisitor($visitor);

        $event = new BeforeEntityAddToIndexEvent($shoppingList);

        $this->listener->checkEntityNeedIndex($event);

        $this->assertNull($event->getEntity());
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testCheckEntityNeedIndexExistedGuestShoppingList(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingListStub::class, ['id' => 123]);
        $visitor = $this->getEntity(CustomerVisitor::class, ['id' => 123]);
        $shoppingList->addVisitor($visitor);

        $event = new BeforeEntityAddToIndexEvent($shoppingList);

        $this->listener->checkEntityNeedIndex($event);

        $this->assertSame($shoppingList, $event->getEntity());
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testCheckEntityNeedIndexNewCustomerShoppingList(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingListStub::class, ['id' => null]);
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 234]);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 345]);
        $shoppingList->setCustomer($customer)->setCustomerUser($customerUser);

        $event = new BeforeEntityAddToIndexEvent($shoppingList);

        $this->listener->checkEntityNeedIndex($event);

        $this->assertSame($shoppingList, $event->getEntity());
        $this->assertFalse($event->isPropagationStopped());
    }
}
