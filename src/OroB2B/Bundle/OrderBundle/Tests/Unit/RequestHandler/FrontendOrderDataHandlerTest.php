<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\RequestHandler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\RequestHandler\FrontendOrderDataHandler;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class FrontendOrderDataHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected $requestStack;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PaymentTermProvider
     */
    protected $paymentTermProvider;

    /**
     * @var FrontendOrderDataHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroUserBundle:User')
            ->willReturn($this->objectManager);

        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTermProvider = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new FrontendOrderDataHandler(
            $this->registry,
            $this->requestStack,
            $this->securityFacade,
            $this->paymentTermProvider
        );
    }

    protected function tearDown()
    {
        unset($this->handler, $this->objectManager, $this->requestStack);
        unset($this->securityFacade, $this->paymentTermProvider);
    }

    public function testGetAccountUser()
    {
        $accountUser = new AccountUser();
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);

        $this->assertSame($accountUser, $this->handler->getAccountUser());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Only AccountUser can create an Order
     */
    public function testGetAccountUserWithoutAccountUser()
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn(new \stdClass());

        $this->handler->getAccountUser();
    }

    public function testGetAccount()
    {
        $account = new Account();
        $accountUser = new AccountUser();
        $accountUser->setAccount($account);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);

        $this->assertSame($account, $this->handler->getAccount());
    }

    public function testGetPaymentTerm()
    {
        $account = new Account();
        $accountUser = new AccountUser();
        $accountUser->setAccount($account);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);

        $paymentTerm = new PaymentTerm();
        $this->paymentTermProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->with($account)
            ->willReturn($paymentTerm);

        $this->assertSame($paymentTerm, $this->handler->getPaymentTerm());
    }

    public function testGetOwner()
    {
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

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
