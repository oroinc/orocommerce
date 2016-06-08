<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Mailer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\RFPBundle\Mailer\Processor;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Mailer\RequestRepresentativesNotifier;

class RequestRepresentativesNotifierTest extends \PHPUnit_Framework_TestCase
{
    /** @var Processor|\PHPUnit_Framework_MockObject_MockObject */
    protected $processor;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var RequestRepresentativesNotifier */
    protected $requestToQuoteRepresentativesNotifier;

    /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request * */
    protected $request;

    /** @var  AccountUser|\PHPUnit_Framework_MockObject_MockObject $accountUser */
    protected $accountUser;

    /** @var  Account|\PHPUnit_Framework_MockObject_MockObject $accountUser */
    protected $account;

    /** @var  User $owner */
    protected $owner;

    /** @var ArrayCollection */
    protected $salesReps;

    protected function setUp()
    {
        $this->processor = $this->getMockBuilder('OroB2B\Bundle\RFPBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMock('OroB2B\Bundle\RFPBundle\Entity\Request');

        $this->requestToQuoteRepresentativesNotifier = new RequestRepresentativesNotifier(
            $this->processor,
            $this->configManager
        );
    }

    public function testNotifyRepresentativesIgnoredIfNoId()
    {
        $this->request->expects($this->never())
            ->method('getAccount');
        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesForAccountUser()
    {
        $this->configureRequestMock();
        $salesReps = new ArrayCollection();
        $salesReps->add(new User());
        $salesReps->add(new User());
        $salesReps->add(new User());
        $this->accountUser->expects($this->once())
            ->method('getSalesRepresentatives')
            ->willReturn($salesReps);
        $this->processor->expects($this->exactly(5))
            ->method('sendRFPNotification');
        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesShouldAlwaysNotifySalesRepsOfAccount()
    {
        $this->configureNotifySalesRepsOfAccountTest();
        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->willReturn('always');
        $this->accountUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn(new ArrayCollection());
        $this->account->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(true);

        $this->processor->expects($this->exactly(4))
            ->method('sendRFPNotification');

        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesShouldNotifySalesRepsOfAccountIfNoUserSalesReps()
    {
        $this->configureNotifySalesRepsOfAccountTest();
        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->willReturn('notalways');
        $this->accountUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn(new ArrayCollection());
        $this->account->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(true);
        $this->accountUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn($this->salesReps);

        $this->processor->expects($this->exactly(3))
            ->method('sendRFPNotification');

        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesShouldNotNotifySalesRepsOfAccount()
    {
        $this->configureNotifySalesRepsOfAccountTest();
        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->willReturn('notalways');
        $this->accountUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn(new ArrayCollection());
        $this->account->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(true);
        $this->accountUser->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(true);

        $this->processor->expects($this->never())
            ->method('sendRFPNotification');

        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesShouldAlwaysNotifyOwnerOfAccount()
    {
        $this->configureNotifySalesRepsOfAccountTest();
        $this->accountUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn(new ArrayCollection());
        $this->account->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(false);
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturn('always');

        $this->processor->expects($this->exactly(2))
            ->method('sendRFPNotification')
            ->with($this->request, $this->owner);

        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesShouldNotifyOwnerOfAccountIfNoUserSalesReps()
    {
        $this->configureNotifySalesRepsOfAccountTest();
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturn('notalways');
        $this->accountUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn(new ArrayCollection());
        $this->account->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(false);

        $this->processor->expects($this->exactly(2))
            ->method('sendRFPNotification');

        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    protected function configureRequestMock()
    {
        $this->owner = new User();
        $this->accountUser = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser');
        $this->accountUser->expects($this->any())
            ->method('getOwner')
            ->willReturn($this->owner);
        $this->account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser');
        $this->account->expects($this->any())
            ->method('getOwner')
            ->willReturn($this->owner);
        $this->request->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->request->expects($this->any())
            ->method('getAccount')
            ->willReturn($this->account);

        $this->request->expects($this->any())
            ->method('getAccountUser')
            ->willReturn($this->accountUser);
    }

    protected function configureNotifySalesRepsOfAccountTest()
    {
        $this->configureRequestMock();
        $this->salesReps = new ArrayCollection();
        $this->salesReps->add(new User());
        $this->salesReps->add(new User());
        $this->account->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn($this->salesReps);
    }
}
