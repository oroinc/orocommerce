<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
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
class PaymentTermProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface */
    private $tokenStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var PaymentTermProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->paymentTermAssociationProvider = $this->createMock(PaymentTermAssociationProvider::class);

        $this->provider = new PaymentTermProvider(
            $this->tokenStorage,
            $this->eventDispatcher,
            $this->paymentTermAssociationProvider
        );
    }

    public function testPaymentTermFromCustomer(): void
    {
        $customer = new Customer();
        $paymentTerm = new PaymentTerm();

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->willReturn($paymentTerm);

        $this->assertSame($paymentTerm, $this->provider->getPaymentTerm($customer));
    }

    public function testPaymentTermFromCustomerWithoutGroup(): void
    {
        $customer = new Customer();

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->willReturn(null);

        $this->assertNull($this->provider->getPaymentTerm($customer));
    }

    public function testPaymentTermFromCustomerGroup(): void
    {
        $customer = new Customer();
        $group = new CustomerGroup();
        $customer->setGroup($group);
        $paymentTerm = new PaymentTerm();

        $this->paymentTermAssociationProvider->expects($this->exactly(2))
            ->method('getPaymentTerm')
            ->willReturnMap([
                [$customer, null, null],
                [$group, null, $paymentTerm],
            ]);

        $this->assertSame($paymentTerm, $this->provider->getPaymentTerm($customer));
    }

    public function testGetCurrentFormResolvePaymentTermEvent(): void
    {
        $paymentTerm = new PaymentTerm();
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ResolvePaymentTermEvent::class), ResolvePaymentTermEvent::NAME)
            ->willReturnCallback(function (ResolvePaymentTermEvent $event) use ($paymentTerm) {
                $event->setPaymentTerm($paymentTerm);

                return $event;
            });
        $this->assertSame($paymentTerm, $this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentWithoutToken(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ResolvePaymentTermEvent::class), ResolvePaymentTermEvent::NAME);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);
        $this->assertNull($this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentWithoutUser(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ResolvePaymentTermEvent::class), ResolvePaymentTermEvent::NAME);
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->assertNull($this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrent(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ResolvePaymentTermEvent::class), ResolvePaymentTermEvent::NAME);
        $paymentTerm = new PaymentTerm();
        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->willReturn($paymentTerm);
        $token = $this->createMock(TokenInterface::class);
        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());
        $token->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($customerUser);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->assertSame($paymentTerm, $this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentForCustomerVisitorWithoutCustomerUser(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ResolvePaymentTermEvent::class), ResolvePaymentTermEvent::NAME);
        $this->paymentTermAssociationProvider->expects($this->never())
            ->method('getPaymentTerm');
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $visitor = new CustomerVisitor();
        $token->expects($this->once())
            ->method('getVisitor')
            ->willReturn($visitor);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->assertNull($this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentForCustomerVisitorWithoutCustomer(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ResolvePaymentTermEvent::class), ResolvePaymentTermEvent::NAME);
        $this->paymentTermAssociationProvider->expects($this->never())
            ->method('getPaymentTerm');
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $visitor = new CustomerVisitor();
        $customerUser = new CustomerUser();
        $visitor->setCustomerUser($customerUser);
        $token->expects($this->exactly(2))
            ->method('getVisitor')
            ->willReturn($visitor);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->assertNull($this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentForCustomerVisitorWithCustomerUser(): void
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ResolvePaymentTermEvent::class), ResolvePaymentTermEvent::NAME);
        $paymentTerm = new PaymentTerm();
        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->willReturn($paymentTerm);
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $visitor = new CustomerVisitor();
        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());
        $visitor->setCustomerUser($customerUser);
        $token->expects($this->exactly(3))
            ->method('getVisitor')
            ->willReturn($visitor);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->assertSame($paymentTerm, $this->provider->getCurrentPaymentTerm());
    }

    public function testCustomerPaymentTermFromOwner(): void
    {
        $customer = new Customer();
        $paymentTerm = new PaymentTerm();
        $owner = PaymentTermAwareStub::create($customer);

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->willReturn($paymentTerm);

        $this->assertSame($paymentTerm, $this->provider->getCustomerPaymentTermByOwner($owner));
    }

    public function testCustomerPaymentTermFromOwnerWithoutCustomer(): void
    {
        $owner = PaymentTermAwareStub::create();

        $this->paymentTermAssociationProvider->expects($this->never())
            ->method('getPaymentTerm');

        $this->assertNull($this->provider->getCustomerPaymentTermByOwner($owner));
    }

    public function testCustomerPaymentTermFromGroupOwnerWithoutCustomer(): void
    {
        $owner = PaymentTermAwareStub::create();

        $this->paymentTermAssociationProvider->expects($this->never())
            ->method('getPaymentTerm');

        $this->assertNull($this->provider->getCustomerGroupPaymentTermByOwner($owner));
    }

    public function testCustomerPaymentTermFromGroupOwnerWithoutCustomerGroup(): void
    {
        $customer = new Customer();
        $owner = PaymentTermAwareStub::create($customer);

        $this->paymentTermAssociationProvider->expects($this->never())
            ->method('getPaymentTerm');

        $this->assertNull($this->provider->getCustomerGroupPaymentTermByOwner($owner));
    }

    public function testCustomerPaymentTermFromGroupOwner(): void
    {
        $paymentTerm = new PaymentTerm();
        $customer = new Customer();
        $group = new CustomerGroup();
        $customer->setGroup($group);
        $owner = PaymentTermAwareStub::create($customer);

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->willReturn($paymentTerm);

        $this->assertSame($paymentTerm, $this->provider->getCustomerGroupPaymentTermByOwner($owner));
    }

    public function testGetObjectPaymentTermNotAnObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Object expected, "array" given');

        $this->provider->getObjectPaymentTerm([]);
    }

    public function testGetObjectPaymentTermFromSecondAssociation(): void
    {
        $paymentTerm = new PaymentTerm();
        $entity = new PaymentTermAwareStub($paymentTerm);

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['firstProp', 'paymentTerm']);

        $this->paymentTermAssociationProvider->expects($this->exactly(2))
            ->method('getPaymentTerm')
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

    public function testGetObjectPaymentTermEmpty(): void
    {
        $paymentTerm = new PaymentTerm();
        $entity = new PaymentTermAwareStub($paymentTerm);

        $this->paymentTermAssociationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['firstProp', 'paymentTerm']);

        $this->paymentTermAssociationProvider->expects($this->exactly(2))
            ->method('getPaymentTerm')
            ->withConsecutive(
                [$entity, 'firstProp'],
                [$entity, 'paymentTerm']
            )
            ->willReturn(null);

        $this->assertNull($this->provider->getObjectPaymentTerm($entity));
    }
}
