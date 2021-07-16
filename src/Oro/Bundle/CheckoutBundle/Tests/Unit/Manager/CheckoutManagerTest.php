<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class CheckoutManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CheckoutManager
     */
    private $checkoutManager;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->checkoutManager = new CheckoutManager($this->doctrineHelper);
    }

    public function testAssignRegisteredCustomerUserToCheckoutNoCheckout()
    {
        $repository = $this->configureGetRepository();
        $repository->expects($this->once())
            ->method('find')
            ->with(777)
            ->willReturn(null);

        $this->checkoutManager->assignRegisteredCustomerUserToCheckout(new CustomerUser(), 777);
    }

    public function testAssignRegisteredCustomerUserToCheckout()
    {
        $repository = $this->configureGetRepository();

        $customerUser = new CustomerUser();
        $checkout = new Checkout();

        $repository->expects($this->once())
            ->method('find')
            ->with(777)
            ->willReturn($checkout);

        $entityManager = $this->configureGetEntityManager();

        $entityManager->expects($this->once())
            ->method('flush')
            ->with($checkout);

        $this->checkoutManager->assignRegisteredCustomerUserToCheckout($customerUser, 777);
        $this->assertSame($customerUser, $checkout->getRegisteredCustomerUser());
    }

    public function testReassignCustomerUserNoCheckout()
    {
        $repository = $this->configureGetRepository();
        $customerUser = new CustomerUser();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['registeredCustomerUser' => $customerUser])
            ->willReturn(null);

        $this->checkoutManager->reassignCustomerUser($customerUser);
    }

    public function testReassignCustomerUser()
    {
        $repository = $this->configureGetRepository();
        $customerUser = new CustomerUser();
        $checkout = (new Checkout())->setRegisteredCustomerUser($customerUser);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['registeredCustomerUser' => $customerUser])
            ->willReturn($checkout);

        $entityManager = $this->configureGetEntityManager();

        $entityManager->expects($this->once())
            ->method('flush')
            ->with($checkout);

        $this->checkoutManager->reassignCustomerUser($customerUser);
        $this->assertNull($checkout->getRegisteredCustomerUser());
        $this->assertSame($customerUser, $checkout->getCustomerUser());
    }

    /**
     * @return EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function configureGetRepository()
    {
        $repository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Checkout::class)
            ->willReturn($repository);

        return $repository;
    }

    /**
     * @return EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private function configureGetEntityManager()
    {
        $repository = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(Checkout::class)
            ->willReturn($repository);

        return $repository;
    }
}
