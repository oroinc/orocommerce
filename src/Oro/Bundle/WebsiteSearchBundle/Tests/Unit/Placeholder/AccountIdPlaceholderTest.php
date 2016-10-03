<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AccountIdPlaceholder;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AccountIdPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /** @var AccountIdPlaceholder */
    private $placeholder;

    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    protected function setUp()
    {
        $this->tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->placeholder = new AccountIdPlaceholder($this->tokenStorage);
    }

    public function testGetPlaceholder()
    {
        $this->assertInternalType('string', $this->placeholder->getPlaceholder());
        $this->assertEquals('ACCOUNT_ID', $this->placeholder->getPlaceholder());
    }

    public function testGetValueWhenTokenIsNull()
    {
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->assertNull($this->placeholder->getValue());
    }

    public function testGetValueWhenAccountUserIsNotAccountUser()
    {
        $token = $this->getMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn('Anonymous');

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertNull($this->placeholder->getValue());
    }

    public function testGetValueWhenAccountUserIsGiven()
    {
        $accountUserId = 7;
        $accountUser = $this->getMockBuilder(AccountUser::class)
            ->getMock();

        $accountUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn($accountUserId);

        $token = $this->getMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($accountUser);

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($accountUserId, $this->placeholder->getValue());
    }

    public function testReplace()
    {
        $this->assertEquals('visibility_account_1', $this->placeholder->replace('visibility_account_ACCOUNT_ID', 1));
    }
}
