<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckoutSinglePage\Operation;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Operation\NewShippingAddress;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewShippingAddressTest extends TestCase
{
    use EntityTrait;

    private ActionExecutor&MockObject $actionExecutor;
    private UpdateShippingPriceInterface&MockObject $updateShippingPrice;
    private DefaultShippingMethodSetterInterface&MockObject $defaultShippingMethodSetter;

    private NewShippingAddress $operation;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->updateShippingPrice = $this->createMock(UpdateShippingPriceInterface::class);
        $this->defaultShippingMethodSetter = $this->createMock(DefaultShippingMethodSetterInterface::class);

        $this->operation = new NewShippingAddress(
            $this->actionExecutor,
            $this->updateShippingPrice,
            $this->defaultShippingMethodSetter
        );
    }

    public function testExecuteWithInvalidEntity(): void
    {
        $data = new ActionData();
        $data->offsetSet('data', new \stdClass());

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Only Checkout entity is supported');

        $this->operation->execute($data);
    }

    public function testExecuteWithValidEntity(): void
    {
        $data = new ActionData();
        $address = new OrderAddress();

        $checkout = $this->getEntity(Checkout::class);
        $checkout->setShippingMethod('test_method');
        $checkout->setShippingAddress($address);

        $data->offsetSet('data', $checkout);
        $data->offsetSet('save_address', true);

        $this->actionExecutor->expects(self::exactly(2))
            ->method('executeAction')
            ->withConsecutive(
                ['flush_entity', [$address]],
                ['flush_entity', [$checkout]]
            );

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->defaultShippingMethodSetter->expects(self::once())
            ->method('setDefaultShippingMethod')
            ->with($checkout);

        $this->operation->execute($data);

        self::assertTrue($checkout->isSaveShippingAddress());
        self::assertNull($checkout->getShippingMethod());
    }

    public function testExecuteWithNullShippingCost(): void
    {
        $data = new ActionData();
        $address = new OrderAddress();

        $checkout = $this->getEntity(Checkout::class);
        $checkout->setShippingMethod('test_method');
        $checkout->setShippingCost(Price::create(10, 'USD'));
        $checkout->setShippingAddress($address);

        $data->offsetSet('data', $checkout);
        $data->offsetSet('save_address', true);

        $this->actionExecutor->expects(self::exactly(2))
            ->method('executeAction')
            ->withConsecutive(
                ['flush_entity', [$address]],
                ['flush_entity', [$checkout]]
            );

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->defaultShippingMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethod');

        $this->operation->execute($data);

        self::assertTrue($checkout->isSaveShippingAddress());
    }

    public function testExecuteWithOldAddress(): void
    {
        $data = new ActionData();
        $address = new OrderAddress();
        $oldAddress = new OrderAddress();
        ReflectionUtil::setId($oldAddress, 42);

        $checkout = $this->getEntity(Checkout::class);
        $checkout->setShippingMethod('test_method');
        $checkout->setShippingCost(Price::create(10, 'USD'));
        $checkout->setShippingAddress($address);

        $data->offsetSet('data', $checkout);
        $data->offsetSet('save_address', true);
        $data->offsetSet('oldAddress', $oldAddress);

        $this->actionExecutor->expects(self::exactly(3))
            ->method('executeAction')
            ->withConsecutive(
                ['remove_entity', [$oldAddress]],
                ['flush_entity', [$address]],
                ['flush_entity', [$checkout]]
            );

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->defaultShippingMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethod');

        $this->operation->execute($data);

        self::assertTrue($checkout->isSaveShippingAddress());
    }
}
