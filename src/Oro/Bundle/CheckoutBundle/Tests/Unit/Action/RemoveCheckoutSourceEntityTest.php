<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CheckoutBundle\Action\RemoveCheckoutSourceEntity;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class RemoveCheckoutSourceEntityTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var ActionInterface
     */
    protected $action;

    /** @var  MockObject|EventDispatcher */
    protected $dispatcher;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();

        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->action = new RemoveCheckoutSourceEntity($this->contextAccessor, $this->registry);

        /** @var EventDispatcher $dispatcher */
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->action->setDispatcher($this->dispatcher);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     * @param array $options
     */
    public function testInitializeException(array $options)
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return [
            [[]],
            [[1, 2]]
        ];
    }

    public function testInitialize()
    {
        $target = new \stdClass();
        $this->action->initialize([$target]);
        $this->assertAttributeEquals($target, 'target', $this->action);
    }

    public function testExecuteNotObjectException()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Action "remove_checkout_source_entity" expects reference to entity as parameter, string is given.'
        );

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch');

        $context = new \stdClass();
        $target = 'checkout';
        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    public function testExecuteIncorrectObjectException()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Action "remove_checkout_source_entity" expects entity instanceof "%s", "stdClass" is given.',
                CheckoutInterface::class
            )
        );

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch');

        $context = new \stdClass();
        $context->checkout = new \stdClass();
        $target = new PropertyPath('checkout');
        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    public function testExecuteObjectWithEmptySourceEntityException()
    {
        $this->dispatcher
            ->expects($this->exactly(2))
            ->method('dispatch');

        $this->registry
            ->expects($this->never())
            ->method('getManagerForClass');

        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $context = new \stdClass();
        $context->checkout = $this->getEntity(Checkout::class, [
            'source' => $checkoutSource
        ]);
        $target = new PropertyPath('checkout');
        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    public function testExecute()
    {
        $this->dispatcher
            ->expects($this->exactly(4))
            ->method('dispatch');

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);

        $checkoutSource = new CheckoutSourceStub();
        $checkoutSource->setShoppingList($shoppingList);

        $context = new \stdClass();
        $context->checkout = $this->getEntity(Checkout::class, [
            'source' => $checkoutSource
        ]);
        $target = new PropertyPath('checkout');

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('remove')
            ->with($shoppingList);

        $this->registry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->will($this->returnValue($em));

        $this->action->initialize([$target]);
        $this->action->execute($context);

        $this->assertNull($checkoutSource->getShoppingList());
        $this->assertNull($checkoutSource->getEntity());
    }
}
