<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridCustomerVisitorAclListener;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class CheckoutGridCustomerVisitorAclListenerTest extends TestCase
{
    /** @var CheckoutGridCustomerVisitorAclListener */
    private $listener;

    /** @var TokenStorage */
    private $tokenStorage;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = new TokenStorage();
        $this->listener = new CheckoutGridCustomerVisitorAclListener($this->tokenStorage);
    }

    public function testOnBuildBeforeWithAnonymousCustomerUserTokenShouldThrowException()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Anonymous users are not allowed.');

        $this->tokenStorage->setToken(new AnonymousCustomerUserToken(new CustomerVisitor()));
        $this->listener->onBuildBefore($this->createMock(BuildBefore::class));
    }

    public function testOnBuildBeforeWithRegularTokenShouldNotThrowException()
    {
        $this->tokenStorage->setToken(new UsernamePasswordToken($this->createMock(UserInterface::class), 'main'));
        $this->listener->onBuildBefore($this->createMock(BuildBefore::class));
    }
}
