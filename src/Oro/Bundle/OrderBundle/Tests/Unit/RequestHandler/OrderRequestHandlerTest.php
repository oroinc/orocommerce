<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\RequestHandler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\RequestHandler\OrderRequestHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderRequestHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Request */
    private $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager */
    private $objectManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $registry;

    /** @var string */
    private $customerClass = Customer::class;

    /** @var string */
    private $customerUserClass = CustomerUser::class;

    /** @var OrderRequestHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManager::class);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->objectManager);

        $this->request = $this->createMock(Request::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->handler = new OrderRequestHandler(
            $this->registry,
            $requestStack,
            $this->customerClass,
            $this->customerUserClass
        );
    }

    /**
     * @dataProvider getCustomerDataProvider
     */
    public function testGetWithoutRequest(string $method)
    {
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->assertNull($this->handler->$method());
    }

    /**
     * @dataProvider getCustomerDataProvider
     */
    public function testGetWithoutParam(string $method)
    {
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->assertNull($this->handler->$method());
    }

    /**
     * @dataProvider getCustomerDataProvider
     */
    public function testGetNotFound(string $method, string $class, string $param)
    {
        $entity = $this->getEntity($class, ['id' => 42]);

        $this->request->expects($this->once())
            ->method('get')
            ->with(OrderType::NAME)
            ->willReturn([$param => $entity->getId()]);

        $this->objectManager->expects($this->once())
            ->method('find')
            ->with($class, $entity->getId())
            ->willReturn(null);

        $this->assertNull($this->handler->$method());
    }

    /**
     * @dataProvider getCustomerDataProvider
     */
    public function testGetCustomer(string $method, string $class, string $param)
    {
        $entity = $this->getEntity($class, ['id' => 42]);

        $this->request->expects($this->once())
            ->method('get')
            ->with(OrderType::NAME)
            ->willReturn([$param => $entity->getId()]);

        $this->objectManager->expects($this->once())
            ->method('find')
            ->with($class, $entity->getId())
            ->willReturn($entity);

        $this->assertSame($entity, $this->handler->$method());
    }

    public function getCustomerDataProvider(): array
    {
        return [
            ['getCustomer', $this->customerClass, 'customer'],
            ['getCustomerUser', $this->customerUserClass, 'customerUser']
        ];
    }
}
