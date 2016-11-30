<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CommerceMenuBundle\Provider\AccountOwnershipProvider;

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

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new AccountOwnershipProvider($registry, '\EntityClass', $this->tokenStorage);
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
        $accountId = 26;
        $account = $this->getMock(Account::class);
        $account->expects($this->once())
            ->method('getId')
            ->willReturn($accountId);
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
        $this->assertEquals($accountId, $this->provider->getId());
    }
}
