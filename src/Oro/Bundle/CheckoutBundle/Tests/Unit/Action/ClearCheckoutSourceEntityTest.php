<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Oro\Bundle\CheckoutBundle\Action\ClearCheckoutSourceEntity;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class ClearCheckoutSourceEntityTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ContextAccessor
     */
    private $contextAccessor;

    /**
     * @var MockObject|EventDispatcher
     */
    private $dispatcher;

    /**
     * @var ClearCheckoutSourceEntity
     */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->action = new ClearCheckoutSourceEntity($this->contextAccessor);
        $this->action->setDispatcher($this->dispatcher);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
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

    public function testExecuteNotObjectException()
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

    public function testExecuteIncorrectObjectException()
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

    public function testExecuteObjectWithEmptySourceEntityException()
    {
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
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList->addLineItem(new LineItem());

        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($shoppingList);

        $context = new \stdClass();
        $context->checkout = $this->getEntity(Checkout::class, [
            'source' => $checkoutSource
        ]);
        $target = new PropertyPath('checkout');

        $this->action->initialize([$target]);
        $this->action->execute($context);
        self::assertCount(0, $shoppingList->getLineItems());
    }
}
