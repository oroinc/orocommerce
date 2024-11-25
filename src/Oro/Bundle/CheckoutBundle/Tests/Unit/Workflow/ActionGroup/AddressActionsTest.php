<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActions;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Duplicator\Duplicator;
use Oro\Component\Duplicator\DuplicatorFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddressActionsTest extends TestCase
{
    private ManagerRegistry|MockObject $registry;
    private DuplicatorFactory|MockObject $duplicatorFactory;
    private ActionExecutor|MockObject $actionExecutor;
    private EntityManagerInterface|MockObject $entityManager;

    private AddressActions $addressActions;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->duplicatorFactory = $this->createMock(DuplicatorFactory::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->addressActions = new AddressActions(
            $this->registry,
            $this->duplicatorFactory,
            $this->actionExecutor
        );
    }

    public function testUpdateBillingAddressWithShippingHasCustomerAddressShipToBilling(): void
    {
        $customerUserAddress = $this->createMock(CustomerUserAddress::class);
        $customerUserAddress->expects($this->any())
            ->method('hasTypeWithName')
            ->with(AddressType::TYPE_SHIPPING)
            ->willReturn(true);

        $billingAddress = $this->createMock(OrderAddress::class);
        $billingAddress->expects($this->any())
            ->method('getCustomerUserAddress')
            ->willReturn($customerUserAddress);
        $shippingAddress = $this->createMock(OrderAddress::class);

        $newShippingAddress = $this->createMock(OrderAddress::class);
        $duplicatorConfig = [[['setNull'], ['propertyName', ['id']]]];

        $customer = new Customer();
        $customerUser = new CustomerUser();

        $sourceEntity = new ShoppingList();
        $checkout = new Checkout();
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($sourceEntity);

        $checkout->setShipToBillingAddress(true);
        $checkout->setSource($checkoutSource);
        $checkout->setCustomer($customer);
        $checkout->setCustomerUser($customerUser);
        $checkout->setBillingAddress($billingAddress);
        $checkout->setShippingAddress($shippingAddress);

        $this->assertUpdateShippingAddressCall(
            $shippingAddress,
            $newShippingAddress,
            $billingAddress,
            $duplicatorConfig
        );

        $this->addressActions->setAddressDuplicatorConfig($duplicatorConfig);
        $this->assertTrue($this->addressActions->updateBillingAddress($checkout, false));

        $this->assertSame($customerUser, $sourceEntity->getCustomerUser());
        $this->assertSame($customer, $sourceEntity->getCustomer());
    }

    public function testUpdateBillingAddressWithShippingHasCustomerUserAddressDoNotShipToBilling(): void
    {
        $customerAddress = $this->createMock(CustomerAddress::class);
        $customerAddress->expects($this->any())
            ->method('hasTypeWithName')
            ->with(AddressType::TYPE_SHIPPING)
            ->willReturn(true);

        $billingAddress = $this->createMock(OrderAddress::class);
        $billingAddress->expects($this->any())
            ->method('getCustomerUserAddress')
            ->willReturn(null);
        $billingAddress->expects($this->any())
            ->method('getCustomerAddress')
            ->willReturn($customerAddress);

        $customer = new Customer();
        $customerUser = new CustomerUser();

        $sourceEntity = new Order();
        $checkout = new Checkout();
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($sourceEntity);

        $checkout->setShipToBillingAddress(false);
        $checkout->setSource($checkoutSource);
        $checkout->setCustomer($customer);
        $checkout->setCustomerUser($customerUser);
        $checkout->setBillingAddress($billingAddress);

        $this->assertTrue($this->addressActions->updateBillingAddress($checkout, false));

        $this->assertNull($sourceEntity->getCustomerUser());
        $this->assertNull($sourceEntity->getCustomer());
    }

    public function testUpdateBillingAddressWithoutShipping(): void
    {
        $checkout = new Checkout();
        $billingAddress = $this->createMock(OrderAddress::class);
        $customerUserAddress = $this->createMock(CustomerUserAddress::class);
        $billingAddress->expects($this->any())
            ->method('getCustomerUserAddress')
            ->willReturn($customerUserAddress);
        $customerUserAddress->expects($this->any())
            ->method('hasTypeWithName')
            ->with(AddressType::TYPE_SHIPPING)
            ->willReturn(false);

        $checkout->setShipToBillingAddress(true);
        $checkout->setBillingAddress($billingAddress);

        $this->assertFalse($this->addressActions->updateBillingAddress($checkout, true));
    }

    public function testUpdateShippingAddress()
    {
        $billingAddress = $this->createMock(OrderAddress::class);
        $shippingAddress = $this->createMock(OrderAddress::class);
        $newShippingAddress = $this->createMock(OrderAddress::class);

        $checkout = new Checkout();
        $checkout->setShipToBillingAddress(true);
        $checkout->setBillingAddress($billingAddress);
        $checkout->setShippingAddress($shippingAddress);

        $duplicatorConfig = [[['setNull'], ['propertyName', ['id']]]];

        $this->assertUpdateShippingAddressCall(
            $shippingAddress,
            $newShippingAddress,
            $billingAddress,
            $duplicatorConfig
        );

        $this->addressActions->setAddressDuplicatorConfig($duplicatorConfig);
        $this->addressActions->updateShippingAddress($checkout);
    }

    public function testDuplicateOrderAddress()
    {
        $shippingAddress = $this->createMock(OrderAddress::class);
        $newShippingAddress = $this->createMock(OrderAddress::class);
        $duplicatorConfig = [[['setNull'], ['propertyName', ['id']]]];

        $this->addressActions->setAddressDuplicatorConfig($duplicatorConfig);
        $this->assertDuplicatorCall($shippingAddress, $newShippingAddress, $duplicatorConfig);

        $this->assertSame($newShippingAddress, $this->addressActions->duplicateOrderAddress($shippingAddress));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testActualizeAddressesWithSavingBothAddresses(): void
    {
        $checkout = new Checkout();
        $order = new Order();
        $organization = new Organization();

        // Setup order addresses with sample data
        $billingAddress = new OrderAddress();
        $billingAddress->setLabel('Billing Address Label');
        $billingAddress->setOrganization('TestOrg1');
        $billingAddress->setStreet('123 Billing St');
        $billingAddress->setStreet2('111');
        $billingAddress->setCity('Billing City');
        $billingAddress->setNamePrefix('Mr.');
        $billingAddress->setFirstName('John');
        $billingAddress->setMiddleName('A.');
        $billingAddress->setLastName('Doe');
        $billingAddress->setNameSuffix('Sr.');
        $billingAddress->setPhone('555-1234');
        $billingAddress->setCountry(new Country('US'));
        $billingAddress->setRegion(new Region('CA'));
        $billingAddress->setPostalCode('90210');

        $shippingAddress = new OrderAddress();
        $shippingAddress->setLabel('Shipping Address Label');
        $shippingAddress->setOrganization('TestOrg2');
        $shippingAddress->setStreet('456 Shipping Ave');
        $shippingAddress->setStreet2('222');
        $shippingAddress->setCity('Shipping City');
        $shippingAddress->setNamePrefix('Ms.');
        $shippingAddress->setFirstName('Jane');
        $shippingAddress->setMiddleName('B.');
        $shippingAddress->setLastName('Smith');
        $shippingAddress->setNameSuffix('Jr.');
        $shippingAddress->setPhone('555-5678');
        $shippingAddress->setCountry(new Country('US'));
        $shippingAddress->setRegion(new Region('NY'));
        $shippingAddress->setPostalCode('10001');

        // Set order addresses
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);

        // Setup checkout details
        $checkoutBillingAddress = new OrderAddress();
        $checkoutShippingAddress = new OrderAddress();
        $user = new User();
        $customerUser = new CustomerUser();
        $customerUser->setFirstName('Checkout');
        $customerUser->setLastName('User');
        $checkout->setCustomerUser($customerUser);
        $checkout->setOwner($user);
        $checkout->setOrganization($organization);
        $checkout->setBillingAddress($checkoutBillingAddress);
        $checkout->setShippingAddress($checkoutShippingAddress);

        // Enable saving addresses in checkout
        $checkout->setSaveBillingAddress(true);
        $checkout->setSaveShippingAddress(true);

        $this->actionExecutor
            ->expects($this->exactly(2))
            ->method('evaluateExpression')
            ->withConsecutive(
                ['acl_granted', ['oro_order_address_billing_allow_manual']],
                ['acl_granted', ['oro_order_address_shipping_allow_manual']]
            )
            ->willReturn(true);

        $billingAddressType = new AddressType(AddressType::TYPE_BILLING);
        $shippingAddressType = new AddressType(AddressType::TYPE_SHIPPING);

        $this->entityManager->expects($this->exactly(2))
            ->method('getReference')
            ->willReturnOnConsecutiveCalls($billingAddressType, $shippingAddressType);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(CustomerUserAddress::class));
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call actualizeAddresses and test
        $this->addressActions->actualizeAddresses($checkout, $order);

        // Assert that addresses have been saved and fields copied correctly
        $actualBillingAddress = $order->getBillingAddress()->getCustomerUserAddress();
        $actualShippingAddress = $order->getShippingAddress()->getCustomerUserAddress();

        $this->assertInstanceOf(CustomerUserAddress::class, $actualBillingAddress);
        $this->assertInstanceOf(CustomerUserAddress::class, $actualShippingAddress);

        $this->assertSame($actualBillingAddress, $checkoutBillingAddress->getCustomerUserAddress());
        $this->assertSame($actualShippingAddress, $checkoutShippingAddress->getCustomerUserAddress());

        // Assert billing address fields
        $this->assertEquals('Billing Address Label', $actualBillingAddress->getLabel());
        $this->assertEquals('TestOrg1', $actualBillingAddress->getOrganization());
        $this->assertEquals('123 Billing St', $actualBillingAddress->getStreet());
        $this->assertEquals('Billing City', $actualBillingAddress->getCity());
        $this->assertEquals('Mr.', $actualBillingAddress->getNamePrefix());
        $this->assertEquals('John', $actualBillingAddress->getFirstName());
        $this->assertEquals('A.', $actualBillingAddress->getMiddleName());
        $this->assertEquals('Doe', $actualBillingAddress->getLastName());
        $this->assertEquals('Sr.', $actualBillingAddress->getNameSuffix());
        $this->assertEquals('555-1234', $actualBillingAddress->getPhone());
        $this->assertEquals('90210', $actualBillingAddress->getPostalCode());
        $this->assertSame($billingAddress->getCountry(), $actualBillingAddress->getCountry());
        $this->assertSame($billingAddress->getRegion(), $actualBillingAddress->getRegion());
        $this->assertTrue($actualBillingAddress->hasTypeWithName(AddressType::TYPE_BILLING));
        $this->assertFalse($actualBillingAddress->hasTypeWithName(AddressType::TYPE_SHIPPING));

        // Assert shipping address fields
        $this->assertEquals('Shipping Address Label', $actualShippingAddress->getLabel());
        $this->assertEquals('TestOrg2', $actualShippingAddress->getOrganization());
        $this->assertEquals('456 Shipping Ave', $actualShippingAddress->getStreet());
        $this->assertEquals('Shipping City', $actualShippingAddress->getCity());
        $this->assertEquals('Ms.', $actualShippingAddress->getNamePrefix());
        $this->assertEquals('Jane', $actualShippingAddress->getFirstName());
        $this->assertEquals('B.', $actualShippingAddress->getMiddleName());
        $this->assertEquals('Smith', $actualShippingAddress->getLastName());
        $this->assertEquals('Jr.', $actualShippingAddress->getNameSuffix());
        $this->assertEquals('555-5678', $actualShippingAddress->getPhone());
        $this->assertEquals('10001', $actualShippingAddress->getPostalCode());
        $this->assertSame($shippingAddress->getCountry(), $actualShippingAddress->getCountry());
        $this->assertSame($shippingAddress->getRegion(), $actualShippingAddress->getRegion());
        $this->assertFalse($actualShippingAddress->hasTypeWithName(AddressType::TYPE_BILLING));
        $this->assertTrue($actualShippingAddress->hasTypeWithName(AddressType::TYPE_SHIPPING));
    }

    public function testActualizeAddressesWithShipToBilling(): void
    {
        $checkout = new Checkout();
        $order = new Order();
        $organization = new Organization();

        // Setup order addresses with sample data
        $billingAddress = new OrderAddress();
        $billingAddress->setLabel('Billing Address Label');
        $billingAddress->setOrganization('TestOrg1');
        $billingAddress->setStreet('123 Billing St');
        $billingAddress->setCity('Billing City');
        $billingAddress->setNamePrefix('Mr.');
        $billingAddress->setFirstName('John');
        $billingAddress->setMiddleName('A.');
        $billingAddress->setLastName('Doe');
        $billingAddress->setNameSuffix('Sr.');
        $billingAddress->setPhone('555-1234');
        $billingAddress->setCountry(new Country('US'));
        $billingAddress->setRegion(new Region('CA'));
        $billingAddress->setPostalCode('90210');

        // Set order addresses
        $order->setBillingAddress($billingAddress);

        // Setup checkout details
        $checkoutBillingAddress = new OrderAddress();
        $user = new User();
        $customerUser = new CustomerUser();
        $customerUser->setFirstName('Checkout');
        $customerUser->setLastName('User');
        $checkout->setCustomerUser($customerUser);
        $checkout->setOwner($user);
        $checkout->setOrganization($organization);
        $checkout->setBillingAddress($checkoutBillingAddress);

        // Enable saving addresses and ship to billing in checkout
        $checkout->setSaveBillingAddress(true);
        $checkout->setSaveShippingAddress(true);
        $checkout->setShipToBillingAddress(true);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('acl_granted', ['oro_order_address_billing_allow_manual'])
            ->willReturn(true);

        // Setup AddressType references
        $billingAddressType = new AddressType(AddressType::TYPE_BILLING);
        $shippingAddressType = new AddressType(AddressType::TYPE_SHIPPING);

        $this->entityManager->expects($this->exactly(2))
            ->method('getReference')
            ->willReturnOnConsecutiveCalls($billingAddressType, $shippingAddressType);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(CustomerUserAddress::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Call actualizeAddresses and test
        $this->addressActions->actualizeAddresses($checkout, $order);

        // Assert that addresses have been saved and fields copied correctly
        $actualBillingAddress = $order->getBillingAddress()->getCustomerUserAddress();
        $this->assertInstanceOf(CustomerUserAddress::class, $actualBillingAddress);

        // Assert billing address fields
        $this->assertEquals('Billing Address Label', $actualBillingAddress->getLabel());
        $this->assertEquals('TestOrg1', $actualBillingAddress->getOrganization());
        $this->assertEquals('123 Billing St', $actualBillingAddress->getStreet());
        $this->assertEquals('Billing City', $actualBillingAddress->getCity());
        $this->assertEquals('Mr.', $actualBillingAddress->getNamePrefix());
        $this->assertEquals('John', $actualBillingAddress->getFirstName());
        $this->assertEquals('A.', $actualBillingAddress->getMiddleName());
        $this->assertEquals('Doe', $actualBillingAddress->getLastName());
        $this->assertEquals('Sr.', $actualBillingAddress->getNameSuffix());
        $this->assertEquals('555-1234', $actualBillingAddress->getPhone());
        $this->assertEquals('90210', $actualBillingAddress->getPostalCode());
        $this->assertSame($billingAddress->getCountry(), $actualBillingAddress->getCountry());
        $this->assertSame($billingAddress->getRegion(), $actualBillingAddress->getRegion());
        $this->assertTrue($actualBillingAddress->hasTypeWithName(AddressType::TYPE_BILLING));
        $this->assertTrue($actualBillingAddress->hasTypeWithName(AddressType::TYPE_SHIPPING));
    }

    public function testActualizeAddressesWithoutSavingAddresses(): void
    {
        $checkout = new Checkout();
        $order = new Order();
        $billingAddress = new OrderAddress();
        $shippingAddress = new OrderAddress();

        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);

        $checkout->setSaveBillingAddress(false);
        $checkout->setSaveShippingAddress(false);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');
        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->addressActions->actualizeAddresses($checkout, $order);

        $this->assertNull($billingAddress->getCustomerUserAddress());
        $this->assertNull($shippingAddress->getCustomerUserAddress());
    }

    private function assertUpdateShippingAddressCall(
        OrderAddress $shippingAddress,
        OrderAddress $newShippingAddress,
        OrderAddress $billingAddress,
        array $duplicatorConfig
    ): void {
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($shippingAddress);
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($newShippingAddress);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->assertDuplicatorCall($billingAddress, $newShippingAddress, $duplicatorConfig);
    }

    private function assertDuplicatorCall(
        OrderAddress $sourceAddress,
        OrderAddress $newAddress,
        array $duplicatorConfig
    ): void {
        $duplicator = $this->createMock(Duplicator::class);
        $duplicator->expects($this->once())
            ->method('duplicate')
            ->with($sourceAddress, $duplicatorConfig)
            ->willReturn($newAddress);
        $this->duplicatorFactory->expects($this->once())
            ->method('create')
            ->willReturn($duplicator);
    }
}
