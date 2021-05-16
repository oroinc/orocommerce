<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;
use Oro\Bundle\OrderBundle\Validator\Constraints\CustomerOrUserAddressGranted;
use Oro\Bundle\OrderBundle\Validator\Constraints\CustomerOrUserAddressGrantedValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CustomerOrUserAddressGrantedValidatorTest extends ConstraintValidatorTestCase
{
    /** @var AddressProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $addressProvider;

    /**
     * {@inheritDoc}
     */
    protected function createValidator()
    {
        $this->addressProvider = $this->createMock(AddressProviderInterface::class);
        return new CustomerOrUserAddressGrantedValidator($this->addressProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new CustomerOrUserAddressGranted();
        $this->propertyPath = null;

        return parent::createContext();
    }

    /**
     * Cannot use EntityTrait because setValue declarations in trait and ConstraintValidatorTestCase are different.
     */
    private function setId($entity, $idValue)
    {
        $reflectionClass = new \ReflectionClass($entity);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $idValue);
    }

    public function testWithInvalidConstraint()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate(new Order(), $this->createMock(Constraint::class));
    }

    public function testWithInvalidEntity()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
        $this->assertNoViolation();
    }

    public function testWithEmptyBillingAddress()
    {
        $this->constraint->addressType = 'billing';
        $this->validator->validate(new Order(), $this->constraint);
        $this->assertNoViolation();
    }

    public function testWithEmptyShippingAddress()
    {
        $this->constraint->addressType = 'shipping';
        $this->validator->validate(new Order(), $this->constraint);
        $this->assertNoViolation();
    }

    public function testWithNotValidBilllingCustomerUserAddress()
    {
        $order = new Order();

        $customerUser = new CustomerUser();
        $order->setCustomerUser($customerUser);

        $customerUserAddress = new CustomerUserAddress();
        $this->setId($customerUserAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerUserAddress($customerUserAddress);
        $order->setBillingAddress($orderAddress);

        $currentUserAddresses1 = new CustomerUserAddress();
        $this->setId($currentUserAddresses1, 1);
        $currentUserAddresses2 = new CustomerUserAddress();
        $this->setId($currentUserAddresses2, 2);

        $this->addressProvider->expects($this->once())
            ->method('getCustomerUserAddresses')
            ->with($customerUser, 'billing')
            ->willReturn([$currentUserAddresses1, $currentUserAddresses2]);

        $this->constraint->addressType = 'billing';
        $this->validator->validate($order, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('billingAddress.customerUserAddress')
            ->assertRaised();
    }

    public function testWithNotValidBilllingCustomerAddress()
    {
        $order = new Order();

        $customer = new Customer();
        $order->setCustomer($customer);

        $customerAddress = new CustomerAddress();
        $this->setId($customerAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerAddress($customerAddress);
        $order->setBillingAddress($orderAddress);

        $currentAddresses1 = new CustomerAddress();
        $this->setId($currentAddresses1, 1);
        $currentAddresses2 = new CustomerAddress();
        $this->setId($currentAddresses2, 2);

        $this->addressProvider->expects($this->once())
            ->method('getCustomerAddresses')
            ->with($customer, 'billing')
            ->willReturn([$currentAddresses1, $currentAddresses2]);

        $this->constraint->addressType = 'billing';
        $this->validator->validate($order, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('billingAddress.customerAddress')
            ->assertRaised();
    }

    public function testWithNotValidShippinggCustomerUserAddress()
    {
        $order = new Order();

        $customerUser = new CustomerUser();
        $order->setCustomerUser($customerUser);

        $customerUserAddress = new CustomerUserAddress();
        $this->setId($customerUserAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerUserAddress($customerUserAddress);
        $order->setShippingAddress($orderAddress);

        $currentUserAddresses1 = new CustomerUserAddress();
        $this->setId($currentUserAddresses1, 1);
        $currentUserAddresses2 = new CustomerUserAddress();
        $this->setId($currentUserAddresses2, 2);

        $this->addressProvider->expects($this->once())
            ->method('getCustomerUserAddresses')
            ->with($customerUser, 'shipping')
            ->willReturn([$currentUserAddresses1, $currentUserAddresses2]);

        $this->constraint->addressType = 'shipping';
        $this->validator->validate($order, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('shippingAddress.customerUserAddress')
            ->assertRaised();
    }

    public function testWithNotValidShippingCustomerAddress()
    {
        $order = new Order();

        $customer = new Customer();
        $order->setCustomer($customer);

        $customerAddress = new CustomerAddress();
        $this->setId($customerAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerAddress($customerAddress);
        $order->setShippingAddress($orderAddress);

        $currentAddresses1 = new CustomerAddress();
        $this->setId($currentAddresses1, 1);
        $currentAddresses2 = new CustomerAddress();
        $this->setId($currentAddresses2, 2);

        $this->addressProvider->expects($this->once())
            ->method('getCustomerAddresses')
            ->with($customer, 'shipping')
            ->willReturn([$currentAddresses1, $currentAddresses2]);

        $this->constraint->addressType = 'shipping';
        $this->validator->validate($order, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('shippingAddress.customerAddress')
            ->assertRaised();
    }

    public function testWithValidBilllingCustomerUserAddress()
    {
        $order = new Order();

        $customerUser = new CustomerUser();
        $order->setCustomerUser($customerUser);

        $customerUserAddress = new CustomerUserAddress();
        $this->setId($customerUserAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerUserAddress($customerUserAddress);
        $order->setBillingAddress($orderAddress);

        $currentUserAddresses1 = new CustomerUserAddress();
        $this->setId($currentUserAddresses1, 123);
        $currentUserAddresses2 = new CustomerUserAddress();
        $this->setId($currentUserAddresses2, 2);

        $this->addressProvider->expects($this->once())
            ->method('getCustomerUserAddresses')
            ->with($customerUser, 'billing')
            ->willReturn([$currentUserAddresses1, $currentUserAddresses2]);

        $this->constraint->addressType = 'billing';
        $this->validator->validate($order, $this->constraint);
        $this->assertNoViolation();
    }

    public function testWithValidBilllingCustomerAddress()
    {
        $order = new Order();

        $customer = new Customer();
        $order->setCustomer($customer);

        $customerAddress = new CustomerAddress();
        $this->setId($customerAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerAddress($customerAddress);
        $order->setBillingAddress($orderAddress);

        $currentAddresses1 = new CustomerAddress();
        $this->setId($currentAddresses1, 123);
        $currentAddresses2 = new CustomerAddress();
        $this->setId($currentAddresses2, 2);

        $this->addressProvider->expects($this->once())
            ->method('getCustomerAddresses')
            ->with($customer, 'billing')
            ->willReturn([$currentAddresses1, $currentAddresses2]);

        $this->constraint->addressType = 'billing';
        $this->validator->validate($order, $this->constraint);
        $this->assertNoViolation();
    }

    public function testWithValidShippinggCustomerUserAddress()
    {
        $order = new Order();

        $customerUser = new CustomerUser();
        $order->setCustomerUser($customerUser);

        $customerUserAddress = new CustomerUserAddress();
        $this->setId($customerUserAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerUserAddress($customerUserAddress);
        $order->setShippingAddress($orderAddress);

        $currentUserAddresses1 = new CustomerUserAddress();
        $this->setId($currentUserAddresses1, 123);
        $currentUserAddresses2 = new CustomerUserAddress();
        $this->setId($currentUserAddresses2, 2);

        $this->addressProvider->expects($this->once())
            ->method('getCustomerUserAddresses')
            ->with($customerUser, 'shipping')
            ->willReturn([$currentUserAddresses1, $currentUserAddresses2]);

        $this->constraint->addressType = 'shipping';
        $this->validator->validate($order, $this->constraint);
        $this->assertNoViolation();
    }

    public function testWithValidShippingCustomerAddress()
    {
        $order = new Order();

        $customer = new Customer();
        $order->setCustomer($customer);

        $customerAddress = new CustomerAddress();
        $this->setId($customerAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerAddress($customerAddress);
        $order->setShippingAddress($orderAddress);

        $currentAddresses1 = new CustomerAddress();
        $this->setId($currentAddresses1, 123);
        $currentAddresses2 = new CustomerAddress();
        $this->setId($currentAddresses2, 2);

        $this->addressProvider->expects($this->once())
            ->method('getCustomerAddresses')
            ->with($customer, 'shipping')
            ->willReturn([$currentAddresses1, $currentAddresses2]);

        $this->constraint->addressType = 'shipping';
        $this->validator->validate($order, $this->constraint);
        $this->assertNoViolation();
    }

    public function testForOrderWithoutCustomerUser()
    {
        $order = new Order();

        $customerUserAddress = new CustomerUserAddress();
        $this->setId($customerUserAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerUserAddress($customerUserAddress);
        $order->setBillingAddress($orderAddress);

        $this->addressProvider->expects($this->never())
            ->method('getCustomerUserAddresses');

        $this->constraint->addressType = 'billing';
        $this->validator->validate($order, $this->constraint);
        $this->assertNoViolation();
    }

    public function testForOrderWithoutCustomer()
    {
        $order = new Order();

        $customerAddress = new CustomerAddress();
        $this->setId($customerAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerAddress($customerAddress);
        $order->setBillingAddress($orderAddress);

        $this->addressProvider->expects($this->never())
            ->method('getCustomerAddresses');

        $this->constraint->addressType = 'billing';
        $this->validator->validate($order, $this->constraint);
        $this->assertNoViolation();
    }
}
