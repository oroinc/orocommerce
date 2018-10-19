<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\RequestHandler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\RequestHandler\FrontendOrderDataHandler;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

class FrontendOrderDataHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RequestStack
     */
    protected $requestStack;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface
     */
    protected $tokenAccessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PaymentTermProvider
     */
    protected $paymentTermProvider;

    /**
     * @var FrontendOrderDataHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->objectManager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroUserBundle:User')
            ->willReturn($this->objectManager);

        $this->requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->paymentTermProvider = $this->getMockBuilder('Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new FrontendOrderDataHandler(
            $this->registry,
            $this->requestStack,
            $this->tokenAccessor,
            $this->paymentTermProvider
        );
    }

    protected function tearDown()
    {
        unset($this->handler, $this->objectManager, $this->requestStack);
        unset($this->tokenAccessor, $this->paymentTermProvider);
    }

    public function testGetCustomerUser()
    {
        $customerUser = new CustomerUser();
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->assertSame($customerUser, $this->handler->getCustomerUser());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Only CustomerUser can create an Order
     */
    public function testGetCustomerUserWithoutCustomerUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(new \stdClass());

        $this->handler->getCustomerUser();
    }

    public function testGetCustomer()
    {
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->assertSame($customer, $this->handler->getCustomer());
    }

    public function testGetPaymentTerm()
    {
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $paymentTerm = new PaymentTerm();
        $this->paymentTermProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn($paymentTerm);

        $this->assertSame($paymentTerm, $this->handler->getPaymentTerm());
    }

    public function testGetOwner()
    {
        $repository = $this->createMock('Doctrine\Common\Persistence\ObjectRepository');

        $user = new User();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn($user);

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->willReturn($repository);

        $this->assertSame($user, $this->handler->getOwner());
    }
}
