<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\EntityListener\AccountListener;
use Oro\Bundle\WebsiteSearchBundle\Driver\AccountPartialUpdateDriverInterface;

class AccountListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountPartialUpdateDriverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $driver;

    /**
     * @var Account
     */
    protected $account;

    /**
     * @var AccountListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->driver = $this->getMockBuilder(AccountPartialUpdateDriverInterface::class)
            ->getMock();

        $this->account = new Account();
        $this->listener = new AccountListener($this->driver);
    }

    public function testPostPersist()
    {
        $this->driver->expects($this->once())
            ->method('createAccountWithoutAccountGroupVisibility')
            ->with($this->account);

        $this->listener->postPersist($this->account);
    }

    public function testPreRemove()
    {
        $this->driver->expects($this->once())
            ->method('deleteAccountVisibility')
            ->with($this->account);

        $this->listener->preRemove($this->account);
    }
}
