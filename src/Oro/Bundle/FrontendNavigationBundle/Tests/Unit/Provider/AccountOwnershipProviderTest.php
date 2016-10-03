<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\FrontendNavigationBundle\Provider\AccountOwnershipProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AccountOwnershipProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountOwnershipProvider
     */
    private $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private $tokenStorage;

    public function setUp()
    {
        $this->tokenStorage = $this->getMock(TokenStorageInterface::class);
        $this->provider = new AccountOwnershipProvider($this->tokenStorage);
    }

    public function testGetType()
    {
        $this->assertEquals('account', $this->provider->getType());
    }

    public function testGetIdWithEmptyToken()
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);
        $this->assertEquals(null, $this->provider->getId());
    }

    public function testGetIdWithDifferentUserClass()
    {
        $user = new \stdClass();
        $token = $this->getMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->assertEquals(null, $this->provider->getId());
    }

    public function testGetId()
    {
        $account = $this->getMock(Account::class);
        $user = $this->getMock(AccountUser::class);
        $user->expects($this->once())
            ->method('getAccount')
            ->willReturn($account);
        $token = $this->getMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->assertEquals($account, $this->provider->getId());
    }
}
