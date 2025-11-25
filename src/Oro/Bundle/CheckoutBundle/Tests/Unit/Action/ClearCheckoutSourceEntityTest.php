<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Action\ClearCheckoutSourceEntity;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutSourceEntityClearEvent;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Event\ExecuteActionEvent;
use Oro\Component\Action\Event\ExecuteActionEvents;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

final class ClearCheckoutSourceEntityTest extends TestCase
{
    use EntityTrait;

    private EventDispatcher&MockObject $dispatcher;
    private ManagerRegistry&MockObject $registry;
    private ObjectManager&MockObject $manager;

    private ClearCheckoutSourceEntity $action;

    #[\Override]
    protected function setUp(): void
    {
        $contextAccessor = new ContextAccessor();
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->manager = $this->createMock(ObjectManager::class);

        $this->registry->expects(self::any())
            ->method('getManager')
            ->willReturn($this->manager);

        $this->action = new ClearCheckoutSourceEntity($contextAccessor);
        $this->action->setDispatcher($this->dispatcher);
        $this->action->setRegistry($this->registry);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInitializeException(array $options): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->action->initialize($options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            [[]],
            [[1, 2]],
        ];
    }

    public function testExecuteNotObjectException(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Action "clear_checkout_source_entity" expects reference to entity as parameter, string is given.'
        );

        $context = new \stdClass();
        $target = 'checkout';
        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    public function testExecuteIncorrectObjectException(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Action "clear_checkout_source_entity" expects entity instanceof "%s", "stdClass" is given.',
                CheckoutInterface::class
            )
        );

        $context = new \stdClass();
        $context->checkout = new \stdClass();
        $target = new PropertyPath('checkout');
        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    public function testExecuteObjectWithEmptySourceEntityException(): void
    {
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn(null);

        $context = new \stdClass();
        $context->checkout = $this->getEntity(Checkout::class, [
            'source' => $checkoutSource,
        ]);
        $target = new PropertyPath('checkout');
        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    public function testExecute(): void
    {
        $lineItem = new LineItem();
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList->addLineItem($lineItem);

        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn($shoppingList);

        $context = new \stdClass();
        $context->checkout = $this->getEntity(Checkout::class, [
            'source' => $checkoutSource,
        ]);

        $this->dispatcher
            ->expects(self::exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [new ExecuteActionEvent($context, $this->action), ExecuteActionEvents::HANDLE_BEFORE],
                [new CheckoutSourceEntityClearEvent($shoppingList), CheckoutSourceEntityClearEvent::NAME],
                [new ExecuteActionEvent($context, $this->action), ExecuteActionEvents::HANDLE_AFTER]
            );

        $target = new PropertyPath('checkout');

        $this->manager->expects(self::once())
            ->method('remove')
            ->with($lineItem);

        $this->action->initialize([$target]);
        $this->action->execute($context);
        self::assertCount(0, $shoppingList->getLineItems());
    }
}
