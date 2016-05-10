<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\OrderBundle\EventListener\Order\OrderPaymentTermEventListener;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class OrderPaymentTermEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var OrderPaymentTermEventListener */
    protected $listener;

    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermProvider;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider')
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
     * @expectedExceptionMessage AccountUser must belong to Account
     */
    public function testThrowExceptionWhenAccountUserHasWrongAccount()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var Account $account1 */
        $account1 = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 1]);

        /** @var Account $account2 */
        $account2 = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 2]);

        $accountUser1 = new AccountUser();
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
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $order = new Order();

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage AccountUser without Account is not allowed
     */
    public function testAccountUserWithoutOrderAccount()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $accountUser = new AccountUser();
        $accountUser->setAccount(new Account());

        $order = new Order();
        $order
            ->setAccountUser($accountUser);

        $this->setValue($order, 'account', null);

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage AccountUser without Account is not allowed
     */
    public function testAccountUserWithoutAccount()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $accountUser = new AccountUser();

        $order = new Order();
        $order
            ->setAccount(new Account())
            ->setAccountUser($accountUser);

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    public function testAccountUserAccountValid()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var Account $account */
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 1]);

        $accountUser = new AccountUser();
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
     * @param Account $account
     * @param PaymentTerm $accountPaymentTerm
     * @param PaymentTerm $accountGroupPaymentTerm
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function testOnOrderEvent(
        Account $account = null,
        PaymentTerm $accountPaymentTerm = null,
        PaymentTerm $accountGroupPaymentTerm = null
    ) {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

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
        $accountWithGroup = new Account();
        $accountWithGroup->setGroup(new AccountGroup());

        $accountWithoutGroup = new Account();

        $paymentTermWithId = $this->getEntity(
            'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm',
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
