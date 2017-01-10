<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Mailer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\RFPBundle\Mailer\Processor;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Mailer\RequestRepresentativesNotifier;

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

    /** @var  CustomerUser|\PHPUnit_Framework_MockObject_MockObject $customerUser */
    protected $customerUser;

    /** @var  Customer|\PHPUnit_Framework_MockObject_MockObject $customerUser */
    protected $customer;

    /** @var  User $owner */
    protected $owner;

    /** @var ArrayCollection */
    protected $salesReps;

    protected function setUp()
    {
        $this->processor = $this->getMockBuilder('Oro\Bundle\RFPBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->createMock('Oro\Bundle\RFPBundle\Entity\Request');

        $this->requestToQuoteRepresentativesNotifier = new RequestRepresentativesNotifier(
            $this->processor,
            $this->configManager
        );
    }

    public function testNotifyRepresentativesIgnoredIfNoId()
    {
        $this->request->expects($this->never())
            ->method('getCustomer');
        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesForCustomerUser()
    {
        $this->configureRequestMock();
        $salesReps = new ArrayCollection();
        $salesReps->add(new User());
        $salesReps->add(new User());
        $salesReps->add(new User());
        $this->customerUser->expects($this->once())
            ->method('getSalesRepresentatives')
            ->willReturn($salesReps);
        $this->processor->expects($this->exactly(5))
            ->method('sendRFPNotification');
        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesShouldAlwaysNotifySalesRepsOfCustomer()
    {
        $this->configureNotifySalesRepsOfCustomerTest();
        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->willReturn('always');
        $this->customerUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn(new ArrayCollection());
        $this->customer->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(true);

        $this->processor->expects($this->exactly(4))
            ->method('sendRFPNotification');

        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesShouldNotifySalesRepsOfCustomerIfNoUserSalesReps()
    {
        $this->configureNotifySalesRepsOfCustomerTest();
        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->willReturn('notalways');
        $this->customerUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn(new ArrayCollection());
        $this->customer->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(true);
        $this->customerUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn($this->salesReps);

        $this->processor->expects($this->exactly(3))
            ->method('sendRFPNotification');

        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesShouldNotNotifySalesRepsOfCustomer()
    {
        $this->configureNotifySalesRepsOfCustomerTest();
        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->willReturn('notalways');
        $this->customerUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn(new ArrayCollection());
        $this->customer->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(true);
        $this->customerUser->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(true);

        $this->processor->expects($this->never())
            ->method('sendRFPNotification');

        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    public function testNotifyRepresentativesShouldAlwaysNotifyOwnerOfCustomer()
    {
        $this->configureNotifySalesRepsOfCustomerTest();
        $this->customerUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn(new ArrayCollection());
        $this->customer->expects($this->any())
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

    public function testNotifyRepresentativesShouldNotifyOwnerOfCustomerIfNoUserSalesReps()
    {
        $this->configureNotifySalesRepsOfCustomerTest();
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturn('notalways');
        $this->customerUser->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn(new ArrayCollection());
        $this->customer->expects($this->any())
            ->method('hasSalesRepresentatives')
            ->willReturn(false);

        $this->processor->expects($this->exactly(2))
            ->method('sendRFPNotification');

        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    protected function configureRequestMock()
    {
        $this->owner = new User();
        $this->customerUser = $this->createMock('Oro\Bundle\CustomerBundle\Entity\CustomerUser');
        $this->customerUser->expects($this->any())
            ->method('getOwner')
            ->willReturn($this->owner);
        $this->customer = $this->createMock('Oro\Bundle\CustomerBundle\Entity\CustomerUser');
        $this->customer->expects($this->any())
            ->method('getOwner')
            ->willReturn($this->owner);
        $this->request->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->request->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customer);

        $this->request->expects($this->any())
            ->method('getCustomerUser')
            ->willReturn($this->customerUser);
    }

    protected function configureNotifySalesRepsOfCustomerTest()
    {
        $this->configureRequestMock();
        $this->salesReps = new ArrayCollection();
        $this->salesReps->add(new User());
        $this->salesReps->add(new User());
        $this->customer->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn($this->salesReps);
    }
}
