<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\RequestHandler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\RequestHandler\OrderRequestHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderRequestHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Request
     */
    protected $request;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var OrderRequestHandler
     */
    protected $handler;

    /**
     * @var string
     */
    protected $customerClass = 'Oro\Bundle\CustomerBundle\Entity\Customer';

    /**
     * @var string
     */
    protected $customerUserClass = 'Oro\Bundle\CustomerBundle\Entity\CustomerUser';

    protected function setUp(): void
    {
        $this->objectManager = $this->createMock('Doctrine\Persistence\ObjectManager');

        $this->registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($this->objectManager);

        $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');

        /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->handler = new OrderRequestHandler(
            $this->registry,
            $requestStack,
            $this->customerClass,
            $this->customerUserClass
        );
    }

    protected function tearDown(): void
    {
        unset($this->handler, $this->objectManager, $this->request, $this->customerClass, $this->customerUserClass);
    }

    /**
     * @dataProvider getCustomerDataProvider
     *
     * @param string $method
     */
    public function testGetWithoutRequest($method)
    {
        $this->registry->expects($this->never())->method('getManagerForClass');
        $this->assertNull($this->handler->$method());
    }

    /**
     * @dataProvider getCustomerDataProvider
     *
     * @param string $method
     */
    public function testGetWithoutParam($method)
    {
        $this->registry->expects($this->never())->method('getManagerForClass');
        $this->assertNull($this->handler->$method());
    }

    /**
     * @dataProvider getCustomerDataProvider
     *
     * @param string $method
     * @param string $class
     * @param string $param
     */
    public function testGetNotFound($method, $class, $param)
    {
        $entity = $this->getEntity($class, ['id' => 42]);

        $this->request->expects($this->once())->method('get')->with(OrderType::NAME)
            ->willReturn([$param => $entity->getId()]);

        $this->objectManager->expects($this->once())
            ->method('find')
            ->with($class, $entity->getId())
            ->willReturn(null);

        $this->assertNull($this->handler->$method());
    }

    /**
     * @dataProvider getCustomerDataProvider
     *
     * @param string $method
     * @param string $class
     * @param string $param
     */
    public function testGetCustomer($method, $class, $param)
    {
        $entity = $this->getEntity($class, ['id' => 42]);

        $this->request->expects($this->once())->method('get')->with(OrderType::NAME)
            ->willReturn([$param => $entity->getId()]);

        $this->objectManager->expects($this->once())
            ->method('find')
            ->with($class, $entity->getId())
            ->willReturn($entity);

        $this->assertSame($entity, $this->handler->$method());
    }

    /**
     * @return array
     */
    public function getCustomerDataProvider()
    {
        return [
            ['getCustomer', $this->customerClass, 'customer'],
            ['getCustomerUser', $this->customerUserClass, 'customerUser']
        ];
    }
}
