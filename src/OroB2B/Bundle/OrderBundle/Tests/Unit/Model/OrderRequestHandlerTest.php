<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Model\OrderRequestHandler;

class OrderRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var OrderRequestHandler
     */
    protected $handler;

    /**
     * @var string
     */
    protected $accountClass = 'OroB2B\Bundle\AccountBundle\Entity\Account';

    /**
     * @var string
     */
    protected $accountUserClass = 'OroB2B\Bundle\AccountBundle\Entity\AccountUser';

    protected function setUp()
    {
        $this->objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($this->objectManager);

        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');

        $this->handler = new OrderRequestHandler($this->registry, $this->accountClass, $this->accountUserClass);
        $this->handler->setRequest($this->request);
    }

    protected function tearDown()
    {
        unset($this->handler, $this->objectManager, $this->request, $this->accountClass, $this->accountUserClass);
    }

    /**
     * @dataProvider getAccountDataProvider
     *
     * @param string $method
     */
    public function testGetWithoutRequest($method)
    {
        $this->handler->setRequest(null);

        $this->registry->expects($this->never())->method('getManagerForClass');
        $this->assertNull($this->handler->$method());
    }

    /**
     * @dataProvider getAccountDataProvider
     *
     * @param string $method
     */
    public function testGetWithoutParam($method)
    {
        $this->registry->expects($this->never())->method('getManagerForClass');
        $this->assertNull($this->handler->$method());
    }

    /**
     * @dataProvider getAccountDataProvider
     *
     * @param string $method
     * @param string $class
     * @param string $param
     */
    public function testGetNotFound($method, $class, $param)
    {
        $entity = $this->getEntity($class, 42);

        $this->request->expects($this->once())->method('get')->with(OrderType::NAME)
            ->willReturn([$param => $entity->getId()]);

        $this->objectManager->expects($this->once())
            ->method('find')
            ->with($class, $entity->getId())
            ->willReturn(null);

        $this->assertNull($this->handler->$method());
    }

    /**
     * @dataProvider getAccountDataProvider
     *
     * @param string $method
     * @param string $class
     * @param string $param
     */
    public function testGetAccount($method, $class, $param)
    {
        $entity = $this->getEntity($class, 42);

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
    public function getAccountDataProvider()
    {
        return [
            ['getAccount', $this->accountClass, 'account'],
            ['getAccountUser', $this->accountUserClass, 'accountUser']
        ];
    }

    /**
     * @param string $class
     * @param int $id
     *
     * @return object
     */
    protected function getEntity($class, $id)
    {
        $entity = new $class();

        $reflection = new \ReflectionProperty($class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);

        return $entity;
    }
}
