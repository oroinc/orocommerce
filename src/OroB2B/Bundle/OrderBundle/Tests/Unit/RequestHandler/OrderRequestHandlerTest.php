<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\RequestHandler;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\RequestHandler\OrderRequestHandler;

class OrderRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

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

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->handler = new OrderRequestHandler(
            $this->registry,
            $requestStack,
            $this->accountClass,
            $this->accountUserClass
        );
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
     * @dataProvider getAccountDataProvider
     *
     * @param string $method
     * @param string $class
     * @param string $param
     */
    public function testGetAccount($method, $class, $param)
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
    public function getAccountDataProvider()
    {
        return [
            ['getAccount', $this->accountClass, 'account'],
            ['getAccountUser', $this->accountUserClass, 'accountUser']
        ];
    }
}
