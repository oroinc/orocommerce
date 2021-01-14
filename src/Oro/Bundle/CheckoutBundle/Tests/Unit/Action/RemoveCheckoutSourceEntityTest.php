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
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class RemoveCheckoutSourceEntityTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ContextAccessor */
    protected $contextAccessor;

    /** @var MockObject|ManagerRegistry */
    protected $registry;

    /** @var ActionInterface */
    protected $action;

    /** @var  MockObject|EventDispatcher */
    protected $dispatcher;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();

        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->action = new class($this->contextAccessor, $this->registry) extends RemoveCheckoutSourceEntity {
            public function xexecuteAction($context)
            {
                $this->executeAction($context);
            }
        };

        /** @var EventDispatcher $dispatcher */
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->action->setDispatcher($this->dispatcher);
    }

    public function testExecuteNotAnObjectException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Action "remove_checkout_source_entity" expects entity instanceof "%s", "string" is given.',
                CheckoutInterface::class
            )
        );

        $context = $this->createContextStub(null);

        $this->action->initialize(['checkout']);
        $this->action->xexecuteAction($context);
    }

    public function testExecuteIncorrectObjectException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Action "remove_checkout_source_entity" expects entity instanceof "%s", "stdClass" is given.',
                CheckoutInterface::class
            )
        );

        $context = $this->createContextStub(new \stdClass());

        $this->action->initialize([new PropertyPath('checkout')]);
        $this->action->xexecuteAction($context);
    }

    public function testExecuteWithCheckoutWithEmptySourceEntity()
    {
        $this->dispatcher->expects(static::never())->method('dispatch');
        $this->registry->expects(static::never())->method('getManagerForClass');

        $context = $this->createContextStub(
            $this->createCheckoutMock(null, null)
        );

        $this->action->initialize([new PropertyPath('checkout')]);
        $this->action->xexecuteAction($context);
    }

    public function testExecute()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->method('getEntity')->willReturn($shoppingList);
        $checkoutSource->expects(static::once())->method('clear');

        $this->dispatcher->expects(static::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    static::logicalAnd(
                        static::isInstanceOf(CheckoutSourceEntityRemoveEvent::class),
                        static::callback(
                            static function (CheckoutSourceEntityRemoveEvent $event) use ($shoppingList) {
                                static::identicalTo($shoppingList)->evaluate(
                                    $event->getCheckoutSourceEntity(),
                                    ' ' . __FILE__ . ':' .  __LINE__
                                );
                                return true;
                            }
                        )
                    ),
                    static::equalTo(CheckoutSourceEntityRemoveEvent::BEFORE_REMOVE)
                ],
                [
                    static::logicalAnd(
                        static::isInstanceOf(CheckoutSourceEntityRemoveEvent::class),
                        static::callback(
                            static function (CheckoutSourceEntityRemoveEvent $event) use ($shoppingList) {
                                static::identicalTo($shoppingList)->evaluate(
                                    $event->getCheckoutSourceEntity(),
                                    ' ' . __FILE__ . ':' .  __LINE__
                                );
                                return true;
                            }
                        )
                    ),
                    static::equalTo(CheckoutSourceEntityRemoveEvent::AFTER_REMOVE)
                ]
            )
        ->willReturnArgument(2);

        $context = $this->createContextStub(
            $this->createCheckoutMock($checkoutSource, $shoppingList)
        );

        $em = $this->createMock(EntityManager::class);
        $em->expects(static::once())->method('remove')->with($shoppingList);

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        $this->action->initialize([new PropertyPath('checkout')]);
        $this->action->xexecuteAction($context);
    }

    private function createContextStub($checkout): object
    {
        return new class($checkout) {
            public $checkout;

            public function __construct($checkout)
            {
                $this->checkout = $checkout;
            }
        };
    }

    /**
     * @return Checkout|MockObject
     */
    private function createCheckoutMock(
        ?MockObject $source,
        ?CheckoutSourceEntityInterface $sourceEntity
    ) {
        $checkout = $this->getMockBuilder(Checkout::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSource', 'getSourceEntity'])
            ->getMock();
        $checkout->method('getSource')->willReturn($source);

        if (null !== $sourceEntity) {
            $checkout->method('getSourceEntity')->willReturn($sourceEntity);
            $source->method('getEntity')->willReturn($sourceEntity);
        }

        return $checkout;
    }
}
