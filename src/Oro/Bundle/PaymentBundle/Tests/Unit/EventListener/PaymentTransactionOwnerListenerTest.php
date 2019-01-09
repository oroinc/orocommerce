<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\EventListener\PaymentTransactionOwnerListener;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;

class PaymentTransactionOwnerListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject */
    private $tokenAccessor;

    /** @var PaymentTransactionOwnerListener */
    private $listener;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);
        $this->listener = new PaymentTransactionOwnerListener($this->tokenAccessor);
    }

    public function testPrePersistOnEmptyToken()
    {
        $transaction = new PaymentTransaction();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->listener->prePersist($transaction, $this->createMock(LifecycleEventArgs::class));

        $this->assertNull($transaction->getOwner());
        $this->assertNull($transaction->getOrganization());
        $this->assertNull($transaction->getFrontendOwner());
    }

    public function testPrePersistSetUserOwner()
    {
        $transaction = new PaymentTransaction();
        $user = new User();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->listener->prePersist($transaction, $this->createMock(LifecycleEventArgs::class));

        $this->assertSame($user, $transaction->getOwner());
        $this->assertNull($transaction->getFrontendOwner());
    }

    public function testPrePersistSetUserOwnerWithExistingOwner()
    {
        $transaction = new PaymentTransaction();
        $existingOwner = new User();
        $transaction->setOwner($existingOwner);
        $user = new User();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->listener->prePersist($transaction, $this->createMock(LifecycleEventArgs::class));

        $this->assertSame($existingOwner, $transaction->getOwner());
    }

    public function testPrePersistSetFrontendUserOwner()
    {
        $transaction = new PaymentTransaction();
        $user = new CustomerUser();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->listener->prePersist($transaction, $this->createMock(LifecycleEventArgs::class));

        $this->assertSame($user, $transaction->getFrontendOwner());
        $this->assertNull($transaction->getOwner());
    }

    public function testPrePersistSetFrontendUserOwnerWithExistingOwner()
    {
        $transaction = new PaymentTransaction();
        $existingOwner = new CustomerUser();
        $transaction->setFrontendOwner($existingOwner);
        $user = new CustomerUser();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->listener->prePersist($transaction, $this->createMock(LifecycleEventArgs::class));

        $this->assertSame($existingOwner, $transaction->getFrontendOwner());
    }

    public function testPrePersistSetOrganization()
    {
        $transaction = new PaymentTransaction();
        $organization = new Organization();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->listener->prePersist($transaction, $this->createMock(LifecycleEventArgs::class));

        $this->assertSame($organization, $transaction->getOrganization());
    }

    public function testPrePersistSetOrganizationWithExistingOrganization()
    {
        $transaction = new PaymentTransaction();
        $existingOrganization = new Organization();
        $transaction->setOrganization($existingOrganization);
        $organization = new Organization();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->listener->prePersist($transaction, $this->createMock(LifecycleEventArgs::class));

        $this->assertSame($existingOrganization, $transaction->getOrganization());
    }
}
