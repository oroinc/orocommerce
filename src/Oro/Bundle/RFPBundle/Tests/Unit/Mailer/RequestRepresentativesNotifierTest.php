<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Mailer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Mailer\Processor;
use Oro\Bundle\RFPBundle\Mailer\RequestRepresentativesNotifier;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RequestRepresentativesNotifierTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var Processor|\PHPUnit\Framework\MockObject\MockObject */
    private $processor;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var RequestRepresentativesNotifier */
    private $requestToQuoteRepresentativesNotifier;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var CustomerUser|\PHPUnit\Framework\MockObject\MockObject */
    private $customerUser;

    /** @var Customer|\PHPUnit\Framework\MockObject\MockObject */
    private $customer;

    /** @var User $owner */
    private $customerUserOwner;

    /** @var User $owner */
    private $customerOwner;

    /** @var ArrayCollection */
    private $salesReps;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(Processor::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->request = $this->createMock(Request::class);

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
        $this->configureRequest();
        $salesReps = new ArrayCollection();
        $salesReps->add($this->getEntity(User::class, ['id' => 1]));
        $salesReps->add($this->getEntity(User::class, ['id' => 2]));
        $salesReps->add($this->getEntity(User::class, ['id' => 3]));
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
            ->withConsecutive(
                [$this->request, $this->customerUserOwner],
                [$this->request, $this->customerOwner]
            );

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

    public function sendConfirmationEmail()
    {
        $customerUser = (new CustomerUser())->setIsGuest(true);
        $request = $this->getEntity(Request::class, ['id' => 1, 'customerUser' => $customerUser]);

        $this->processor->expects($this->once())
            ->method('sendConfirmation')
            ->with($request, $customerUser);

        $this->requestToQuoteRepresentativesNotifier->sendConfirmationEmail($request);
    }

    /**
     * @dataProvider confirmationEmailIncorrectRequestDataProvider
     */
    public function sendConfirmationEmailIncorrectRequest(array $requestData)
    {
        $request = $this->getEntity(Request::class, $requestData);

        $this->processor->expects($this->never())
            ->method('sendConfirmation');

        $this->requestToQuoteRepresentativesNotifier->sendConfirmationEmail($request);
    }

    public function confirmationEmailIncorrectRequestDataProvider(): array
    {
        return [
            'without customer user' => [
                'requestData' => [
                    'id' => 1,
                    'customerUser' => null
                ]
            ],
            'customer user is not guest' => [
                'requestData' => [
                    'id' => 1,
                    'customerUser' => new CustomerUser()
                ]
            ],
            'request is not created' => [
                'requestData' => [
                    'id' => null,
                    'customerUser' => (new CustomerUser())->setIsGuest(true)
                ]
            ]
        ];
    }

    public function testNotifyOnlyUniqueUsers()
    {
        $user = $this->getEntity(User::class, ['id' => 1]);

        $this->customerUser = $this->getEntity(CustomerUser::class, [
            'owner' => $user,
            'salesRepresentatives' => new ArrayCollection([$user])
        ]);

        $this->customer = $this->getEntity(Customer::class, [
            'owner' => $user,
            'salesRepresentatives' => new ArrayCollection([$user])
        ]);

        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->willReturn('always');

        $this->request->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customer);

        $this->request->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->request->expects($this->any())
            ->method('getCustomerUser')
            ->willReturn($this->customerUser);

        $this->processor->expects($this->once())
            ->method('sendRFPNotification');

        $this->requestToQuoteRepresentativesNotifier->notifyRepresentatives($this->request);
    }

    private function configureRequest()
    {
        $this->customerUserOwner = $this->getEntity(User::class, ['id' => 42, 'username' => 'customerUserOwner']);
        $this->customerOwner = $this->getEntity(User::class, ['id' => 77, 'username' => 'customerOwner']);

        $this->customerUser = $this->createMock(CustomerUser::class);
        $this->customerUser->expects($this->any())
            ->method('getOwner')
            ->willReturn($this->customerUserOwner);
        $this->customer = $this->createMock(CustomerUser::class);
        $this->customer->expects($this->any())
            ->method('getOwner')
            ->willReturn($this->customerOwner);
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

    private function configureNotifySalesRepsOfCustomerTest()
    {
        $this->configureRequest();
        $this->salesReps = new ArrayCollection();
        $this->salesReps->add(new User());
        $this->salesReps->add(new User());
        $this->customer->expects($this->any())
            ->method('getSalesRepresentatives')
            ->willReturn($this->salesReps);
    }
}
