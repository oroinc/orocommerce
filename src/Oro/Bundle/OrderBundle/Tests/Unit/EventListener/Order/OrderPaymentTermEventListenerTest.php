<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderPaymentTermEventListener;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrderPaymentTermEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermProvider;

    /** @var OrderPaymentTermEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->paymentTermProvider = $this->createMock(PaymentTermProvider::class);

        $this->listener = new OrderPaymentTermEventListener($this->paymentTermProvider);
    }

    public function testThrowExceptionWhenCustomerUserHasWrongCustomer()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('CustomerUser must belong to Customer');

        $form = $this->createMock(FormInterface::class);

        $customer1 = $this->getEntity(Customer::class, ['id' => 1]);
        $customer2 = $this->getEntity(Customer::class, ['id' => 2]);

        $customerUser1 = new CustomerUser();
        $customerUser1->setCustomer($customer1);

        $order = new Order();
        $order
            ->setCustomerUser($customerUser1)
            ->setCustomer($customer2);

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    public function testSkipValidationWithoutCustomerUser()
    {
        $form = $this->createMock(FormInterface::class);

        $order = new Order();

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    public function testCustomerUserWithoutOrderCustomer()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('CustomerUser without Customer is not allowed');

        $form = $this->createMock(FormInterface::class);

        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());

        $order = new Order();
        $order
            ->setCustomerUser($customerUser);

        $this->setValue($order, 'customer', null);

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    public function testCustomerUserWithoutCustomer()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('CustomerUser without Customer is not allowed');

        $form = $this->createMock(FormInterface::class);

        $customerUser = new CustomerUser();

        $order = new Order();
        $order
            ->setCustomer(new Customer())
            ->setCustomerUser($customerUser);

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    public function testCustomerUserCustomerValid()
    {
        $form = $this->createMock(FormInterface::class);

        $customer = $this->getEntity(Customer::class, ['id' => 1]);

        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);

        $order = new Order();
        $order
            ->setCustomerUser($customerUser)
            ->setCustomer($customer);

        $event = new OrderEvent($form, $order);
        $this->listener->onOrderEvent($event);
    }

    /**
     * @dataProvider onOrderEventProvider
     */
    public function testOnOrderEvent(
        Customer $customer = null,
        PaymentTerm $customerPaymentTerm = null,
        PaymentTerm $customerGroupPaymentTerm = null
    ) {
        $form = $this->createMock(FormInterface::class);

        $order = new Order();
        $order->setCustomer($customer);

        $this->paymentTermProvider->expects($customer ? $this->once() : $this->never())
            ->method('getCustomerPaymentTerm')
            ->with($customer)
            ->willReturn($customerPaymentTerm);

        $customerHasGroup = $customer && $customer->getGroup();

        $this->paymentTermProvider->expects($customerHasGroup ? $this->once() : $this->never())
            ->method('getCustomerGroupPaymentTerm')
            ->with($customerHasGroup ? $customer->getGroup() : null)
            ->willReturn($customerGroupPaymentTerm);

        $event = new OrderEvent($form, $order);

        $this->listener->onOrderEvent($event);

        $actualData = $event->getData()->getArrayCopy();

        $this->assertArrayHasKey(OrderPaymentTermEventListener::ACCOUNT_PAYMENT_TERM_KEY, $actualData);
        $this->assertArrayHasKey(OrderPaymentTermEventListener::ACCOUNT_GROUP_PAYMENT_TERM_KEY, $actualData);

        $this->assertEquals(
            $customerPaymentTerm?->getId(),
            $actualData[OrderPaymentTermEventListener::ACCOUNT_PAYMENT_TERM_KEY]
        );

        $this->assertEquals(
            $customerGroupPaymentTerm?->getId(),
            $actualData[OrderPaymentTermEventListener::ACCOUNT_GROUP_PAYMENT_TERM_KEY]
        );
    }

    public function onOrderEventProvider(): array
    {
        $customerWithGroup = new Customer();
        $customerWithGroup->setGroup(new CustomerGroup());

        $customerWithoutGroup = new Customer();

        $paymentTermWithId = $this->getEntity(
            PaymentTerm::class,
            ['id' => 1]
        );

        return [
            'without customer' => [
                'customer' => null,
                'customerPaymentTerm' => null,
                'customerGroupPaymentTerm' => null
            ],
            'customer with group (group payment term found)' => [
                'customer' => $customerWithGroup,
                'customerPaymentTerm' => $paymentTermWithId,
                'customerGroupPaymentTerm' => $paymentTermWithId
            ],
            'customer with group (group payment term not found)' => [
                'customer' => $customerWithGroup,
                'customerPaymentTerm' => $paymentTermWithId,
                'customerGroupPaymentTerm' => null
            ],
            'customer without group (customer payment term found)' => [
                'customer' => $customerWithoutGroup,
                'customerPaymentTerm' => $paymentTermWithId,
                'customerGroupPaymentTerm' => null
            ],
            'customer without group (customer payment term not found)' => [
                'customer' => $customerWithoutGroup,
                'customerPaymentTerm' => null,
                'customerGroupPaymentTerm' => null
            ],
        ];
    }
}
