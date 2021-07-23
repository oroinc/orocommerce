<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;

class OrderAddressManagerTest extends AbstractAddressManagerTest
{
    /** @var OrderAddressManager */
    protected $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OrderAddressProvider */
    protected $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $registry;

    protected function setUp(): void
    {
        $this->provider = $this->getMockBuilder('Oro\Bundle\OrderBundle\Provider\OrderAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');

        $this->manager = new OrderAddressManager(
            $this->provider,
            $this->registry,
            'Oro\Bundle\OrderBundle\Entity\OrderAddress'
        );
    }

    protected function tearDown(): void
    {
        unset($this->manager, $this->provider, $this->registry);
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
    ) {
        $classMetadata = $this->createMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $classMetadata->expects($this->once())->method('getFieldNames')->willReturn(['street', 'city', 'label']);
        $classMetadata->expects($this->once())->method('getAssociationNames')
            ->willReturn(['country', 'region']);

        $em = $this->createMock('Doctrine\Persistence\ObjectManager');
        $em->expects($this->once())->method('getClassMetadata')->willReturn($classMetadata);

        $this->registry->expects($this->any())->method('getManagerForClass')->with($this->isType('string'))
            ->willReturn($em);

        $orderAddress = $this->manager->updateFromAbstract($address, $orderAddress);
        $this->assertEquals($expected, $orderAddress);
        $this->assertEquals($expectedCustomerAddress, $orderAddress->getCustomerAddress());
        $this->assertEquals($expectedCustomerUserAddress, $orderAddress->getCustomerUserAddress());
    }

    /**
     * @return array
     */
    public function orderDataProvider()
    {
        $country = new Country('US');
        $region = new Region('US-AL');

        return [
            'empty customer address' => [
                $customerAddress = new CustomerAddress(),
                (new OrderAddress())
                    ->setCustomerAddress($customerAddress),
                $customerAddress
            ],
            'empty customer user address' => [
                $customerUserAddress = new CustomerUserAddress(),
                (new OrderAddress())
                    ->setCustomerUserAddress($customerUserAddress),
                null,
                $customerUserAddress
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
                $customerAddress
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
                $customerUserAddress
            ],
            'do not override value from existing with empty one' => [
                $customerUserAddress = (new CustomerUserAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new OrderAddress())
                    ->setCustomerUserAddress($customerUserAddress)
                    ->setLabel('ExistingLabel')
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                null,
                $customerUserAddress,
                (new OrderAddress())
                    ->setLabel('ExistingLabel')
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
    ) {
        $this->provider->expects($this->any())->method('getCustomerAddresses')->willReturn($customerAddresses);
        $this->provider->expects($this->any())->method('getCustomerUserAddresses')->willReturn($customerUserAddresses);

        $this->manager->addEntity('au', 'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress');
        $this->manager->addEntity('a', 'Oro\Bundle\CustomerBundle\Entity\CustomerAddress');

        $result = $this->manager->getGroupedAddresses($order, AddressType::TYPE_BILLING);

        $this->assertInstanceOf(TypedOrderAddressCollection::class, $result);
        $this->assertEquals($expected, $result->toArray());
    }

    /**
     * @return array
     */
    public function groupedAddressDataProvider()
    {
        return [
            'empty customer user' => [new Order()],
            'empty customer' => [
                (new Order())->setCustomerUser(new CustomerUser()),
                [],
                [
                    $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress', 1),
                    $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress', 2),
                ],
                [
                    'oro.order.form.address.group_label.customer_user' => [
                        'au_1' => $this->getEntity(
                            'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress',
                            1
                        ),
                        'au_2' => $this->getEntity(
                            'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress',
                            2
                        ),
                    ],
                ],
            ],
            'customer' => [
                (new Order())->setCustomerUser(new CustomerUser())->setCustomer(new Customer()),
                [
                    $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerAddress', 1),
                    $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerAddress', 2),
                ],
                [],
                [
                    'oro.order.form.address.group_label.customer' => [
                        'a_1' => $this->getEntity(
                            'Oro\Bundle\CustomerBundle\Entity\CustomerAddress',
                            1
                        ),
                        'a_2' => $this->getEntity(
                            'Oro\Bundle\CustomerBundle\Entity\CustomerAddress',
                            2
                        ),
                    ],
                ],
            ],
            'full' => [
                (new Order())->setCustomerUser(new CustomerUser())->setCustomer(new Customer()),
                [
                    $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerAddress', 1),
                    $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerAddress', 2),
                ],
                [
                    $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress', 1),
                    $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress', 2),
                ],
                [
                    'oro.order.form.address.group_label.customer' => [
                        'a_1' => $this->getEntity(
                            'Oro\Bundle\CustomerBundle\Entity\CustomerAddress',
                            1
                        ),
                        'a_2' => $this->getEntity(
                            'Oro\Bundle\CustomerBundle\Entity\CustomerAddress',
                            2
                        ),
                    ],
                    'oro.order.form.address.group_label.customer_user' => [
                        'au_1' => $this->getEntity(
                            'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress',
                            1
                        ),
                        'au_2' => $this->getEntity(
                            'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress',
                            2
                        ),
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
    ) {
        $customerManager = $this->getManager(
            $customerAddresses,
            $this->getTypes($customerAddresses, ['billing'])
        );
        $customerUserManager = $this->getManager(
            $customerUserAddresses,
            $this->getTypes($customerUserAddresses, ['billing', 'shipping'])
        );

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap(
                [
                    ['OroCustomerBundle:CustomerAddressToAddressType', $customerManager],
                    ['OroCustomerBundle:CustomerUserAddressToAddressType', $customerUserManager]
                ]
            );

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

        $this->manager->addEntity('au', 'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress');
        $this->manager->addEntity('a', 'Oro\Bundle\CustomerBundle\Entity\CustomerAddress');
        $this->assertEquals($expectedTypes, $this->manager->getAddressTypes($addresses));
    }

    /**
     * @param array $addresses
     * @param array $types
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getManager(array $addresses, $types)
    {
        $repo = $this->createMock('\Doctrine\Persistence\ObjectRepository');
        $manager = $this->createMock('\Doctrine\Persistence\ObjectManager');
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('findBy')
            ->with(['address' => $addresses])
            ->willReturn($types);

        return $manager;
    }

    /**
     * @param array $addresses
     * @param array $types
     * @return array
     */
    protected function getTypes(array $addresses, array $types)
    {
        $result = [];
        foreach ($addresses as $address) {
            foreach ($types as $type) {
                $typeEntity = new AddressType($type);
                $typeToEntity = $this
                    ->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\AbstractAddressToAddressType')
                    ->disableOriginalConstructor()
                    ->setMethods(['getAddress', 'getType'])
                    ->getMockForAbstractClass();
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
}
