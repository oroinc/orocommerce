<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderPaymentTermEventListener;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

class OrderPaymentTermEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var OrderPaymentTermEventListener */
    protected $listener;

    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermProvider;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder('Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new OrderPaymentTermEventListener($this->paymentTermProvider);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->paymentTermProvider);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage CustomerUser must belong to Account
     */
    public function testThrowExceptionWhenAccountUserHasWrongAccount()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        /** @var Customer $account1 */
        $account1 = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', ['id' => 1]);

        /** @var Customer $account2 */
        $account2 = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', ['id' => 2]);

        $accountUser1 = new CustomerUser();
        $accountUser1->setAccount($account1);

        $order = new Order();
        $order
            ->setAccountUser($accountUser1)
            ->setAccount($account2);

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    public function testSkipValidationWithoutAccountUser()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $order = new Order();

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage CustomerUser without Account is not allowed
     */
    public function testAccountUserWithoutOrderAccount()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $accountUser = new CustomerUser();
        $accountUser->setAccount(new Customer());

        $order = new Order();
        $order
            ->setAccountUser($accountUser);

        $this->setValue($order, 'account', null);

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage CustomerUser without Account is not allowed
     */
    public function testAccountUserWithoutAccount()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $accountUser = new CustomerUser();

        $order = new Order();
        $order
            ->setAccount(new Customer())
            ->setAccountUser($accountUser);

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    public function testAccountUserAccountValid()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        /** @var Customer $account */
        $account = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', ['id' => 1]);

        $accountUser = new CustomerUser();
        $accountUser->setAccount($account);

        $order = new Order();
        $order
            ->setAccountUser($accountUser)
            ->setAccount($account);

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    /**
     * @dataProvider onOrderEventProvider
     * @param Customer $account
     * @param PaymentTerm $accountPaymentTerm
     * @param PaymentTerm $accountGroupPaymentTerm
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function testOnOrderEvent(
        Customer $account = null,
        PaymentTerm $accountPaymentTerm = null,
        PaymentTerm $accountGroupPaymentTerm = null
    ) {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $order = new Order();
        $order->setAccount($account);

        $this->paymentTermProvider
            ->expects($account ? $this->once() : $this->never())
            ->method('getAccountPaymentTerm')
            ->with($account)
            ->willReturn($accountPaymentTerm);

        $accountHasGroup = $account && $account->getGroup();

        $this->paymentTermProvider
            ->expects($accountHasGroup ? $this->once() : $this->never())
            ->method('getAccountGroupPaymentTerm')
            ->with($accountHasGroup ? $account->getGroup() : null)
            ->willReturn($accountGroupPaymentTerm);

        $event = new OrderEvent($form, $order);

        $this->listener->onOrderEvent($event);

        $actualData = $event->getData()->getArrayCopy();

        $this->assertArrayHasKey(OrderPaymentTermEventListener::ACCOUNT_PAYMENT_TERM_KEY, $actualData);
        $this->assertArrayHasKey(OrderPaymentTermEventListener::ACCOUNT_GROUP_PAYMENT_TERM_KEY, $actualData);

        $this->assertEquals(
            $accountPaymentTerm ? $accountPaymentTerm->getId() : null,
            $actualData[OrderPaymentTermEventListener::ACCOUNT_PAYMENT_TERM_KEY]
        );

        $this->assertEquals(
            $accountGroupPaymentTerm ? $accountGroupPaymentTerm->getId() : null,
            $actualData[OrderPaymentTermEventListener::ACCOUNT_GROUP_PAYMENT_TERM_KEY]
        );
    }

    /**
     * @return array
     */
    public function onOrderEventProvider()
    {
        $accountWithGroup = new Customer();
        $accountWithGroup->setGroup(new CustomerGroup());

        $accountWithoutGroup = new Customer();

        $paymentTermWithId = $this->getEntity(
            'Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm',
            ['id' => 1]
        );

        return [
            'without account' => [
                'account' => null,
                'accountPaymentTerm' => null,
                'accountGroupPaymentTerm' => null
            ],
            'account with group (group payment term found)' => [
                'account' => $accountWithGroup,
                'accountPaymentTerm' => $paymentTermWithId,
                'accountGroupPaymentTerm' => $paymentTermWithId
            ],
            'account with group (group payment term not found)' => [
                'account' => $accountWithGroup,
                'accountPaymentTerm' => $paymentTermWithId,
                'accountGroupPaymentTerm' => null
            ],
            'account without group (account payment term found)' => [
                'account' => $accountWithoutGroup,
                'accountPaymentTerm' => $paymentTermWithId,
                'accountGroupPaymentTerm' => null
            ],
            'account without group (account payment term not found)' => [
                'account' => $accountWithoutGroup,
                'accountPaymentTerm' => null,
                'accountGroupPaymentTerm' => null
            ],
        ];
    }
}
