<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\LoginOnCheckoutListener;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\Request;

class LoginOnCheckoutListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoginOnCheckoutListener
     */
    private $listener;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var InteractiveLoginEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var Request
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(InteractiveLoginEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new LoginOnCheckoutListener($this->logger, $this->configManager, $this->doctrineHelper);

        $this->request = new Request();
    }

    /**
     * @param bool $isCustomer
     * @return CustomerUser|\stdClass
     */
    private function configureToken($isCustomer = true)
    {
        $customerUser = $isCustomer ? new CustomerUser() : new \stdClass();
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->event->expects($this->once())
            ->method('getAuthenticationToken')
            ->willReturn($token);

        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        return $customerUser;
    }

    public function testOnInteractiveLoginNotLogged()
    {
        $this->configureToken(false);
        $this->configManager->expects($this->never())->method('get');
        $this->listener->onInteractiveLogin($this->event);
    }

    public function testOnInteractiveLoginCheckoutIdNotPassed()
    {
        $this->configureToken();
        $this->configManager->expects($this->never())->method('get');
        $this->listener->onInteractiveLogin($this->event);
    }

    public function testOnInteractiveLoginConfigurationDisabled()
    {
        $this->configureToken();
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())->method('getEntityRepository');
        $this->listener->onInteractiveLogin($this->event);
    }

    public function testOnInteractiveLoginWrongCheckout()
    {
        $this->configureToken();
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);
        $repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Checkout::class)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('find')
            ->with(777)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with("Wrong checkout id - 777 passed during login from checkout");

        $this->listener->onInteractiveLogin($this->event);
    }

    public function testOnInteractiveLoginCheckoutAssigned()
    {
        $this->configureToken();
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);
        $repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Checkout::class)
            ->willReturn($repository);

        $checkout = new Checkout();

        $repository->expects($this->once())
            ->method('find')
            ->with(777)
            ->willReturn($checkout);

        $this->logger->expects($this->never())->method('warning');

        $entityManager = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(Checkout::class)
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('flush')
            ->with($checkout);

        $this->listener->onInteractiveLogin($this->event);
    }
}
