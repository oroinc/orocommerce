<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\CustomerBundle\Driver\AccountPartialUpdateDriverInterface;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\EntityListener\AccountListener;
use Oro\Bundle\CustomerBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AccountListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $producer;

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
        $this->factory = $this->getMockBuilder(MessageFactoryInterface::class)
            ->getMock();
        $this->producer = $this->getMockBuilder(MessageProducerInterface::class)
            ->getMock();
        $this->driver = $this->getMockBuilder(AccountPartialUpdateDriverInterface::class)
            ->getMock();

        $this->account = new Account();
        $this->listener = new AccountListener($this->factory, $this->producer, $this->driver);
    }

    public function testPostPersistWithoutGroup()
    {
        $this->producer->expects($this->never())
            ->method('send');
        $this->driver->expects($this->once())
            ->method('createAccountWithoutAccountGroupVisibility')
            ->with($this->account);

        $this->listener->postPersist($this->account);
    }

    public function testPostPersistWithGroup()
    {
        $message = new Message();
        $this->factory->expects($this->once())
            ->method('createMessage')
            ->with($this->account)
            ->willReturn($message);
        $this->producer->expects($this->once())
            ->method('send')
            ->with('', $message);
        $this->driver->expects($this->never())
            ->method('createAccountWithoutAccountGroupVisibility');

        $this->account->setGroup(new AccountGroup());
        $this->listener->postPersist($this->account);
    }

    public function testPreRemove()
    {
        $this->driver->expects($this->once())
            ->method('deleteAccountVisibility')
            ->with($this->account);

        $this->listener->preRemove($this->account);
    }

    public function testPreUpdate()
    {
        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('group')
            ->willReturn(true);

        $message = new Message();
        $this->factory->expects($this->once())
            ->method('createMessage')
            ->with($this->account)
            ->willReturn($message);
        $this->producer->expects($this->once())
            ->method('send')
            ->with('', $message);

        $this->listener->preUpdate($this->account, $args);
    }
}
