<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Entity\AbstractAddressToAddressType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddressToAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddressToAddressType;
use Oro\Bundle\CustomerBundle\Utils\AddressCopier;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\AbstractAddressManager;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class OrderAddressManagerTest extends AbstractAddressManagerTest
{
    private OrderAddressManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $addressCopier = new AddressCopier($this->doctrine, new PropertyAccessor());

        $this->manager = new OrderAddressManager(
            $this->doctrine,
            $this->addressProvider,
            $addressCopier
        );
    }

    #[\Override]
    protected function getAddressManager(): AbstractAddressManager
    {
        return $this->manager;
    }

    private function getAddressTypes(array $addresses, array $types): array
    {
        $result = [];
        foreach ($addresses as $address) {
            foreach ($types as $type) {
                $typeEntity = new AddressType($type);
                $typeToEntity = $this->createMock(AbstractAddressToAddressType::class);
                $typeToEntity->expects($this->any())
                    ->method('getAddress')
                    ->willReturn($address);
                $typeToEntity->expects($this->any())
                    ->method('getType')
                    ->willReturn($typeEntity);
                $result[] = $typeToEntity;
            }
        }

        return $result;
    }

    /**
     * @dataProvider orderDataProvider
     */
    public function testUpdateFromAbstract(
        AbstractAddress $address,
        OrderAddress $expected = null,
        AbstractAddress $expectedCustomerAddress = null,
        AbstractAddress $expectedCustomerUserAddress = null,
        OrderAddress $orderAddress = null
    ): void {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['street', 'city', 'label']);
        $classMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['country', 'region']);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->isType('string'))
            ->willReturn($em);

        $orderAddress = $this->manager->updateFromAbstract($address, $orderAddress);
        self::assertEquals($expected, $orderAddress);
        self::assertSame($expectedCustomerAddress, $orderAddress->getCustomerAddress());
        self::assertSame($expectedCustomerUserAddress, $orderAddress->getCustomerUserAddress());
    }

    public function orderDataProvider(): array
    {
        $country = new Country('US');
        $region = new Region('US-AL');

        return [
            'empty customer address' => [
                $customerAddress = new CustomerAddress(),
                (new OrderAddress())
                    ->setCustomerAddress($customerAddress),
                $customerAddress,
            ],
            'empty customer user address' => [
                $customerUserAddress = new CustomerUserAddress(),
                (new OrderAddress())
                    ->setCustomerUserAddress($customerUserAddress),
                null,
                $customerUserAddress,
            ],
            'from customer address' => [
                $customerAddress = (new CustomerAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new OrderAddress())
                    ->setCustomerAddress($customerAddress)
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                $customerAddress,
            ],
            'from customer user address' => [
                $customerUserAddress = (new CustomerUserAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new OrderAddress())
                    ->setCustomerUserAddress($customerUserAddress)
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                null,
                $customerUserAddress,
            ],
            'overrides value from existing with empty one' => [
                $customerUserAddress = (new CustomerUserAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new OrderAddress())
                    ->setCustomerUserAddress($customerUserAddress)
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                null,
                $customerUserAddress,
                (new OrderAddress())
                    ->setLabel('ExistingLabel'),
            ],
        ];
    }

    /**
     * @dataProvider groupedAddressDataProvider
     */
    public function testGetGroupedAddresses(
        Order $order,
        array $customerAddresses = [],
        array $customerUserAddresses = [],
        array $expected = []
    ): void {
        $this->addressProvider->expects($this->any())
            ->method('getCustomerAddresses')
            ->willReturn($customerAddresses);
        $this->addressProvider->expects($this->any())
            ->method('getCustomerUserAddresses')
            ->willReturn($customerUserAddresses);

        $this->manager->addEntity('au', CustomerUserAddress::class);
        $this->manager->addEntity('a', CustomerAddress::class);

        $result = $this->manager->getGroupedAddresses($order, AddressType::TYPE_BILLING);

        self::assertEquals($expected, $result->toArray());
    }

    public function groupedAddressDataProvider(): array
    {
        return [
            'empty customer user' => [new Order()],
            'empty customer' => [
                (new Order())->setCustomerUser(new CustomerUser()),
                [],
                [$this->getCustomerUserAddress(1), $this->getCustomerUserAddress(2)],
                [
                    'oro.order.form.address.group_label.customer_user' => [
                        'au_1' => $this->getCustomerUserAddress(1),
                        'au_2' => $this->getCustomerUserAddress(2),
                    ],
                ],
            ],
            'customer' => [
                (new Order())->setCustomerUser(new CustomerUser())->setCustomer(new Customer()),
                [$this->getCustomerAddress(1), $this->getCustomerAddress(2)],
                [],
                [
                    'oro.order.form.address.group_label.customer' => [
                        'a_1' => $this->getCustomerAddress(1),
                        'a_2' => $this->getCustomerAddress(2),
                    ],
                ],
            ],
            'full' => [
                (new Order())->setCustomerUser(new CustomerUser())->setCustomer(new Customer()),
                [$this->getCustomerAddress(1), $this->getCustomerAddress(2)],
                [$this->getCustomerUserAddress(1), $this->getCustomerUserAddress(2)],
                [
                    'oro.order.form.address.group_label.customer' => [
                        'a_1' => $this->getCustomerAddress(1),
                        'a_2' => $this->getCustomerAddress(2),
                    ],
                    'oro.order.form.address.group_label.customer_user' => [
                        'au_1' => $this->getCustomerUserAddress(1),
                        'au_2' => $this->getCustomerUserAddress(2),
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider groupedAddressDataProvider
     */
    public function testGetAddressTypes(
        Order $order,
        array $customerAddresses = [],
        array $customerUserAddresses = [],
        array $addresses = []
    ): void {
        $repoCustomerAddressToAddressType = $this->createMock(EntityRepository::class);
        $repoCustomerUserAddressToAddressType = $this->createMock(EntityRepository::class);
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [CustomerAddressToAddressType::class, null, $repoCustomerAddressToAddressType],
                [CustomerUserAddressToAddressType::class, null, $repoCustomerUserAddressToAddressType],
            ]);
        $repoCustomerAddressToAddressType->expects($this->any())
            ->method('findBy')
            ->with(['address' => $customerAddresses])
            ->willReturn($this->getAddressTypes($customerAddresses, ['billing']));
        $repoCustomerUserAddressToAddressType->expects($this->any())
            ->method('findBy')
            ->with(['address' => $customerUserAddresses])
            ->willReturn($this->getAddressTypes($customerUserAddresses, ['billing', 'shipping']));

        $expectedTypes = [];
        if (array_key_exists('oro.order.form.address.group_label.customer', $addresses)) {
            foreach ($addresses['oro.order.form.address.group_label.customer'] as $id => $address) {
                $expectedTypes[$id] = ['billing'];
            }
        }
        if (array_key_exists('oro.order.form.address.group_label.customer_user', $addresses)) {
            foreach ($addresses['oro.order.form.address.group_label.customer_user'] as $id => $address) {
                $expectedTypes[$id] = ['billing', 'shipping'];
            }
        }

        $this->manager->addEntity('au', CustomerUserAddress::class);
        $this->manager->addEntity('a', CustomerAddress::class);
        self::assertEquals($expectedTypes, $this->manager->getAddressTypes($addresses));
    }
}
