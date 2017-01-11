<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Tests\Unit\PaymentTermAwareStub;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PaymentTermProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PaymentTermAssociationProvider
     */
    protected $paymentTermAssociationProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var PaymentTermProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->paymentTermAssociationProvider = $this->getMockBuilder(PaymentTermAssociationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PaymentTermProvider(
            $this->tokenStorage,
            $this->eventDispatcher,
            $this->paymentTermAssociationProvider
        );
    }

    public function testPaymentTermFromCustomer()
    {
        $customer = new Customer();
        $paymentTerm = new PaymentTerm();

        $this->paymentTermAssociationProvider->expects($this->once())->method('getPaymentTerm')
            ->willReturn($paymentTerm);

        $this->assertSame($paymentTerm, $this->provider->getPaymentTerm($customer));
    }

    public function testPaymentTermFromCustomerWithoutGroup()
    {
        $customer = new Customer();

        $this->paymentTermAssociationProvider->expects($this->once())->method('getPaymentTerm')
            ->willReturn(null);

        $this->assertNull($this->provider->getPaymentTerm($customer));
    }

    public function testPaymentTermFromCustomerGroup()
    {
        $customer = new Customer();
        $group = new CustomerGroup();
        $customer->setGroup($group);
        $paymentTerm = new PaymentTerm();

        $this->paymentTermAssociationProvider->expects($this->exactly(2))->method('getPaymentTerm')
            ->willReturnMap(
                [
                    [$customer, null, null],
                    [$group, null, $paymentTerm],
                ]
            );

        $this->assertSame($paymentTerm, $this->provider->getPaymentTerm($customer));
    }

    public function testGetCurrentFormResolvePaymentTermEvent()
    {
        $paymentTerm = new PaymentTerm();
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(ResolvePaymentTermEvent::NAME), $this->isInstanceOf(ResolvePaymentTermEvent::class))
            ->will(
                $this->returnCallback(
                    function ($eventName, ResolvePaymentTermEvent $event) use ($paymentTerm) {
                        $event->setPaymentTerm($paymentTerm);
                    }
                )
            );
        $this->assertSame($paymentTerm, $this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentWithoutToken()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(ResolvePaymentTermEvent::NAME), $this->isInstanceOf(ResolvePaymentTermEvent::class));
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn(null);
        $this->assertNull($this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentWithoutUser()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(ResolvePaymentTermEvent::NAME), $this->isInstanceOf(ResolvePaymentTermEvent::class));
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn(null);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);
        $this->assertNull($this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrent()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(ResolvePaymentTermEvent::NAME), $this->isInstanceOf(ResolvePaymentTermEvent::class));
        $paymentTerm = new PaymentTerm();
        $this->paymentTermAssociationProvider->expects($this->once())->method('getPaymentTerm')
            ->willReturn($paymentTerm);
        $token = $this->createMock(TokenInterface::class);
        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());
        $token->expects($this->once())->method('getUser')->willReturn($customerUser);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);
        $this->assertSame($paymentTerm, $this->provider->getCurrentPaymentTerm());
    }

    public function testCustomerPaymentTermFromOwner()
    {
        $customer = new Customer();
        $paymentTerm = new PaymentTerm();
        $owner = PaymentTermAwareStub::create($customer);

        $this->paymentTermAssociationProvider->expects($this->once())->method('getPaymentTerm')
            ->willReturn($paymentTerm);

        $this->assertSame($paymentTerm, $this->provider->getCustomerPaymentTermByOwner($owner));
    }

    public function testCustomerPaymentTermFromOwnerWithoutCustomer()
    {
        $owner = PaymentTermAwareStub::create();

        $this->paymentTermAssociationProvider->expects($this->never())->method('getPaymentTerm');

        $this->assertNull($this->provider->getCustomerPaymentTermByOwner($owner));
    }

    public function testCustomerPaymentTermFromGroupOwnerWithoutCustomer()
    {
        $owner = PaymentTermAwareStub::create();

        $this->paymentTermAssociationProvider->expects($this->never())->method('getPaymentTerm');

        $this->assertNull($this->provider->getCustomerGroupPaymentTermByOwner($owner));
    }

    public function testCustomerPaymentTermFromGroupOwnerWithoutCustomerGroup()
    {
        $customer = new Customer();
        $owner = PaymentTermAwareStub::create($customer);

        $this->paymentTermAssociationProvider->expects($this->never())->method('getPaymentTerm');

        $this->assertNull($this->provider->getCustomerGroupPaymentTermByOwner($owner));
    }

    public function testCustomerPaymentTermFromGroupOwner()
    {
        $paymentTerm = new PaymentTerm();
        $customer = new Customer();
        $group = new CustomerGroup();
        $customer->setGroup($group);
        $owner = PaymentTermAwareStub::create($customer);

        $this->paymentTermAssociationProvider->expects($this->once())->method('getPaymentTerm')
            ->willReturn($paymentTerm);

        $this->assertSame($paymentTerm, $this->provider->getCustomerGroupPaymentTermByOwner($owner));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object expected, "array" given
     */
    public function testGetObjectPaymentTermNotAnObject()
    {
        $this->provider->getObjectPaymentTerm([]);
    }

    public function testGetObjectPaymentTermFromSecondAssociation()
    {
        $paymentTerm = new PaymentTerm();
        $entity = new PaymentTermAwareStub($paymentTerm);

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['firstProp', 'paymentTerm']);

        $this->paymentTermAssociationProvider->expects($this->exactly(2))->method('getPaymentTerm')
            ->withConsecutive(
                [$entity, 'firstProp'],
                [$entity, 'paymentTerm']
            )
            ->willReturnOnConsecutiveCalls(null, $paymentTerm);

        $this->assertSame(
            $paymentTerm,
            $this->provider->getObjectPaymentTerm($entity)
        );
    }

    public function testGetObjectPaymentTermEmpty()
    {
        $paymentTerm = new PaymentTerm();
        $entity = new PaymentTermAwareStub($paymentTerm);

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['firstProp', 'paymentTerm']);

        $this->paymentTermAssociationProvider->expects($this->exactly(2))->method('getPaymentTerm')
            ->withConsecutive(
                [$entity, 'firstProp'],
                [$entity, 'paymentTerm']
            )
            ->willReturn(null);

        $this->assertNull($this->provider->getObjectPaymentTerm($entity));
    }
}
