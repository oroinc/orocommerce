<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\OrderBundle\EventListener\Order\MatchingPriceEventListener;
use OroB2B\Bundle\OrderBundle\EventListener\Order\OrderPaymentTermEventListener;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class OrderPaymentTermEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var MatchingPriceEventListener */
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
