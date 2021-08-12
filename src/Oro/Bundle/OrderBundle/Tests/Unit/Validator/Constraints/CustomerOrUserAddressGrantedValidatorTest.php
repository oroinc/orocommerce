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
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CustomerOrUserAddressGrantedValidatorTest extends ConstraintValidatorTestCase
{
    private AddressProviderInterface|\PHPUnit\Framework\MockObject\MockObject $addressProvider;

    protected function setUp(): void
    {
        $this->addressProvider = $this->createMock(AddressProviderInterface::class);

        parent::setUp();

        $this->setPropertyPath('');
    }

    protected function createValidator(): CustomerOrUserAddressGrantedValidator
    {
        return new CustomerOrUserAddressGrantedValidator($this->addressProvider);
    }

    public function testWithInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new Order(), $this->createMock(Constraint::class));
    }

    public function testWithInvalidEntity(): void
    {
        $this->validator->validate(new \stdClass(), new CustomerOrUserAddressGranted());
        $this->assertNoViolation();
    }

    public function testWithEmptyBillingAddress(): void
    {
        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'billing';
        $this->validator->validate(new Order(), $constraint);
        $this->assertNoViolation();
    }

    public function testWithEmptyShippingAddress(): void
    {
        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'shipping';
        $this->validator->validate(new Order(), $constraint);
        $this->assertNoViolation();
    }

    public function testWithNotValidBilllingCustomerUserAddress(): void
    {
        $order = new Order();

        $customerUser = new CustomerUser();
        $order->setCustomerUser($customerUser);

        $customerUserAddress = new CustomerUserAddress();
        ReflectionUtil::setId($customerUserAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerUserAddress($customerUserAddress);
        $order->setBillingAddress($orderAddress);

        $currentUserAddresses1 = new CustomerUserAddress();
        ReflectionUtil::setId($currentUserAddresses1, 1);
        $currentUserAddresses2 = new CustomerUserAddress();
        ReflectionUtil::setId($currentUserAddresses2, 2);

        $this->addressProvider->expects(self::once())
            ->method('getCustomerUserAddresses')
            ->with($customerUser, 'billing')
            ->willReturn([$currentUserAddresses1, $currentUserAddresses2]);

        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'billing';
        $this->validator->validate($order, $constraint);
        $this->buildViolation($constraint->message)
            ->atPath('billingAddress.customerUserAddress')
            ->assertRaised();
    }

    public function testWithNotValidBilllingCustomerAddress(): void
    {
        $order = new Order();

        $customer = new Customer();
        $order->setCustomer($customer);

        $customerAddress = new CustomerAddress();
        ReflectionUtil::setId($customerAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerAddress($customerAddress);
        $order->setBillingAddress($orderAddress);

        $currentAddresses1 = new CustomerAddress();
        ReflectionUtil::setId($currentAddresses1, 1);
        $currentAddresses2 = new CustomerAddress();
        ReflectionUtil::setId($currentAddresses2, 2);

        $this->addressProvider->expects(self::once())
            ->method('getCustomerAddresses')
            ->with($customer, 'billing')
            ->willReturn([$currentAddresses1, $currentAddresses2]);

        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'billing';
        $this->validator->validate($order, $constraint);
        $this->buildViolation($constraint->message)
            ->atPath('billingAddress.customerAddress')
            ->assertRaised();
    }

    public function testWithNotValidShippinggCustomerUserAddress(): void
    {
        $order = new Order();

        $customerUser = new CustomerUser();
        $order->setCustomerUser($customerUser);

        $customerUserAddress = new CustomerUserAddress();
        ReflectionUtil::setId($customerUserAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerUserAddress($customerUserAddress);
        $order->setShippingAddress($orderAddress);

        $currentUserAddresses1 = new CustomerUserAddress();
        ReflectionUtil::setId($currentUserAddresses1, 1);
        $currentUserAddresses2 = new CustomerUserAddress();
        ReflectionUtil::setId($currentUserAddresses2, 2);

        $this->addressProvider->expects(self::once())
            ->method('getCustomerUserAddresses')
            ->with($customerUser, 'shipping')
            ->willReturn([$currentUserAddresses1, $currentUserAddresses2]);

        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'shipping';
        $this->validator->validate($order, $constraint);
        $this->buildViolation($constraint->message)
            ->atPath('shippingAddress.customerUserAddress')
            ->assertRaised();
    }

    public function testWithNotValidShippingCustomerAddress(): void
    {
        $order = new Order();

        $customer = new Customer();
        $order->setCustomer($customer);

        $customerAddress = new CustomerAddress();
        ReflectionUtil::setId($customerAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerAddress($customerAddress);
        $order->setShippingAddress($orderAddress);

        $currentAddresses1 = new CustomerAddress();
        ReflectionUtil::setId($currentAddresses1, 1);
        $currentAddresses2 = new CustomerAddress();
        ReflectionUtil::setId($currentAddresses2, 2);

        $this->addressProvider->expects(self::once())
            ->method('getCustomerAddresses')
            ->with($customer, 'shipping')
            ->willReturn([$currentAddresses1, $currentAddresses2]);

        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'shipping';
        $this->validator->validate($order, $constraint);
        $this->buildViolation($constraint->message)
            ->atPath('shippingAddress.customerAddress')
            ->assertRaised();
    }

    public function testWithValidBilllingCustomerUserAddress(): void
    {
        $order = new Order();

        $customerUser = new CustomerUser();
        $order->setCustomerUser($customerUser);

        $customerUserAddress = new CustomerUserAddress();
        ReflectionUtil::setId($customerUserAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerUserAddress($customerUserAddress);
        $order->setBillingAddress($orderAddress);

        $currentUserAddresses1 = new CustomerUserAddress();
        ReflectionUtil::setId($currentUserAddresses1, 123);
        $currentUserAddresses2 = new CustomerUserAddress();
        ReflectionUtil::setId($currentUserAddresses2, 2);

        $this->addressProvider->expects(self::once())
            ->method('getCustomerUserAddresses')
            ->with($customerUser, 'billing')
            ->willReturn([$currentUserAddresses1, $currentUserAddresses2]);

        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'billing';
        $this->validator->validate($order, $constraint);
        $this->assertNoViolation();
    }

    public function testWithValidBilllingCustomerAddress(): void
    {
        $order = new Order();

        $customer = new Customer();
        $order->setCustomer($customer);

        $customerAddress = new CustomerAddress();
        ReflectionUtil::setId($customerAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerAddress($customerAddress);
        $order->setBillingAddress($orderAddress);

        $currentAddresses1 = new CustomerAddress();
        ReflectionUtil::setId($currentAddresses1, 123);
        $currentAddresses2 = new CustomerAddress();
        ReflectionUtil::setId($currentAddresses2, 2);

        $this->addressProvider->expects(self::once())
            ->method('getCustomerAddresses')
            ->with($customer, 'billing')
            ->willReturn([$currentAddresses1, $currentAddresses2]);

        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'billing';
        $this->validator->validate($order, $constraint);
        $this->assertNoViolation();
    }

    public function testWithValidShippinggCustomerUserAddress(): void
    {
        $order = new Order();

        $customerUser = new CustomerUser();
        $order->setCustomerUser($customerUser);

        $customerUserAddress = new CustomerUserAddress();
        ReflectionUtil::setId($customerUserAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerUserAddress($customerUserAddress);
        $order->setShippingAddress($orderAddress);

        $currentUserAddresses1 = new CustomerUserAddress();
        ReflectionUtil::setId($currentUserAddresses1, 123);
        $currentUserAddresses2 = new CustomerUserAddress();
        ReflectionUtil::setId($currentUserAddresses2, 2);

        $this->addressProvider->expects(self::once())
            ->method('getCustomerUserAddresses')
            ->with($customerUser, 'shipping')
            ->willReturn([$currentUserAddresses1, $currentUserAddresses2]);

        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'shipping';
        $this->validator->validate($order, $constraint);
        $this->assertNoViolation();
    }

    public function testWithValidShippingCustomerAddress(): void
    {
        $order = new Order();

        $customer = new Customer();
        $order->setCustomer($customer);

        $customerAddress = new CustomerAddress();
        ReflectionUtil::setId($customerAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerAddress($customerAddress);
        $order->setShippingAddress($orderAddress);

        $currentAddresses1 = new CustomerAddress();
        ReflectionUtil::setId($currentAddresses1, 123);
        $currentAddresses2 = new CustomerAddress();
        ReflectionUtil::setId($currentAddresses2, 2);

        $this->addressProvider->expects(self::once())
            ->method('getCustomerAddresses')
            ->with($customer, 'shipping')
            ->willReturn([$currentAddresses1, $currentAddresses2]);

        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'shipping';
        $this->validator->validate($order, $constraint);
        $this->assertNoViolation();
    }

    public function testForOrderWithoutCustomerUser(): void
    {
        $order = new Order();

        $customerUserAddress = new CustomerUserAddress();
        ReflectionUtil::setId($customerUserAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerUserAddress($customerUserAddress);
        $order->setBillingAddress($orderAddress);

        $this->addressProvider->expects(self::never())
            ->method('getCustomerUserAddresses');

        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'billing';
        $this->validator->validate($order, $constraint);
        $this->assertNoViolation();
    }

    public function testForOrderWithoutCustomer(): void
    {
        $order = new Order();

        $customerAddress = new CustomerAddress();
        ReflectionUtil::setId($customerAddress, 123);
        $orderAddress = new OrderAddress();
        $orderAddress->setCustomerAddress($customerAddress);
        $order->setBillingAddress($orderAddress);

        $this->addressProvider->expects(self::never())
            ->method('getCustomerAddresses');

        $constraint = new CustomerOrUserAddressGranted();
        $constraint->addressType = 'billing';
        $this->validator->validate($order, $constraint);
        $this->assertNoViolation();
    }
}
