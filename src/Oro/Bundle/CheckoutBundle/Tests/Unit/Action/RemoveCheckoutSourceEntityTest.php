<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Action\RemoveCheckoutSourceEntity;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutSourceEntityRemoveEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class RemoveCheckoutSourceEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var ActionInterface */
    private $action;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new class(new ContextAccessor(), $this->registry) extends RemoveCheckoutSourceEntity {
            public function execute($context)
            {
                $this->executeAction($context);
            }
        };
        $this->action->setDispatcher($this->dispatcher);
    }

    public function testExecuteNotAnObjectException(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(sprintf(
            'Action "remove_checkout_source_entity" expects entity instanceof "%s", "string" is given.',
            CheckoutInterface::class
        ));

        $context = $this->createContext(null);

        $this->action->initialize(['checkout']);
        $this->action->execute($context);
    }

    public function testExecuteIncorrectObjectException(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(sprintf(
            'Action "remove_checkout_source_entity" expects entity instanceof "%s", "stdClass" is given.',
            CheckoutInterface::class
        ));

        $context = $this->createContext(new \stdClass());

        $this->action->initialize([new PropertyPath('checkout')]);
        $this->action->execute($context);
    }

    public function testExecuteWithCheckoutWithEmptySourceEntity(): void
    {
        $this->dispatcher->expects(self::never())
            ->method('dispatch');
        $this->registry->expects(self::never())
            ->method('getManagerForClass');

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::never())
            ->method('getSource');

        $context = $this->createContext($checkout);

        $this->action->initialize([new PropertyPath('checkout')]);
        $this->action->execute($context);
    }

    public function testExecute(): void
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, 1);

        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects(self::never())
            ->method('getEntity');
        $checkoutSource->expects(self::once())
            ->method('clear');

        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    self::logicalAnd(
                        self::isInstanceOf(CheckoutSourceEntityRemoveEvent::class),
                        self::callback(function (CheckoutSourceEntityRemoveEvent $event) use ($shoppingList) {
                            self::identicalTo($shoppingList)->evaluate(
                                $event->getCheckoutSourceEntity(),
                                ' ' . __FILE__ . ':' .  __LINE__
                            );

                            return true;
                        })
                    ),
                    CheckoutSourceEntityRemoveEvent::BEFORE_REMOVE
                ],
                [
                    self::logicalAnd(
                        self::isInstanceOf(CheckoutSourceEntityRemoveEvent::class),
                        self::callback(function (CheckoutSourceEntityRemoveEvent $event) use ($shoppingList) {
                            self::identicalTo($shoppingList)->evaluate(
                                $event->getCheckoutSourceEntity(),
                                ' ' . __FILE__ . ':' .  __LINE__
                            );

                            return true;
                        })
                    ),
                    CheckoutSourceEntityRemoveEvent::AFTER_REMOVE
                ]
            )
        ->willReturnArgument(0);

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getSource')
            ->willReturn($checkoutSource);
        $checkout->expects(self::once())
            ->method('getSourceEntity')
            ->willReturn($shoppingList);

        $context = $this->createContext($checkout);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('remove')
            ->with($shoppingList);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        $this->action->initialize([new PropertyPath('checkout')]);
        $this->action->execute($context);
    }

    private function createContext(?object $checkout): object
    {
        return new class($checkout) {
            public ?object $checkout;

            public function __construct(?object $checkout)
            {
                $this->checkout = $checkout;
            }
        };
    }
}
