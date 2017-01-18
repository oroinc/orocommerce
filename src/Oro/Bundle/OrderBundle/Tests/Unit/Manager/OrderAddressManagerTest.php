<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;

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
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;

class OrderAddressManagerTest extends AbstractAddressManagerTest
{
    /** @var OrderAddressManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderAddressProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder('Oro\Bundle\OrderBundle\Provider\OrderAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->manager = new OrderAddressManager(
            $this->provider,
            $this->registry,
            'Oro\Bundle\OrderBundle\Entity\OrderAddress'
        );
    }

    protected function tearDown()
    {
        unset($this->manager, $this->provider, $this->registry);
    }

    /**
     * @param AbstractAddress $address
     * @param OrderAddress|null $expected
     * @param AbstractAddress|null $expectedCustomerAddress
     * @param AbstractAddress|null $expectedCustomerUserAddress
     * @param OrderAddress|null $orderAddress
     *
     * @dataProvider orderDataProvider
     */
    public function testUpdateFromAbstract(
        AbstractAddress $address,
        OrderAddress $expected = null,
        AbstractAddress $expectedCustomerAddress = null,
        AbstractAddress $expectedCustomerUserAddress = null,
        OrderAddress $orderAddress = null
    ) {
        $classMetadata = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $classMetadata->expects($this->once())->method('getFieldNames')->willReturn(['street', 'city', 'label']);
        $classMetadata->expects($this->once())->method('getAssociationNames')
            ->willReturn(['country', 'region']);

        $em = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
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
     * @param Order $order
     * @param array $customerAddresses
     * @param array $customerUserAddresses
     * @param array $expected
     *
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

        $this->assertEquals($expected, $this->manager->getGroupedAddresses($order, AddressType::TYPE_BILLING));
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
                    OrderAddressManager::ACCOUNT_USER_LABEL => [
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
                    OrderAddressManager::ACCOUNT_LABEL => [
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
                    OrderAddressManager::ACCOUNT_LABEL => [
                        'a_1' => $this->getEntity(
                            'Oro\Bundle\CustomerBundle\Entity\CustomerAddress',
                            1
                        ),
                        'a_2' => $this->getEntity(
                            'Oro\Bundle\CustomerBundle\Entity\CustomerAddress',
                            2
                        ),
                    ],
                    OrderAddressManager::ACCOUNT_USER_LABEL => [
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
     * @param Order $order
     * @param array $customerAddresses
     * @param array $customerUserAddresses
     * @param array $addresses
     *
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
        if (array_key_exists(OrderAddressManager::ACCOUNT_LABEL, $addresses)) {
            foreach ($addresses[OrderAddressManager::ACCOUNT_LABEL] as $id => $address) {
                $expectedTypes[$id] = ['billing'];
            }
        }
        if (array_key_exists(OrderAddressManager::ACCOUNT_USER_LABEL, $addresses)) {
            foreach ($addresses[OrderAddressManager::ACCOUNT_USER_LABEL] as $id => $address) {
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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManager(array $addresses, $types)
    {
        $repo = $this->createMock('\Doctrine\Common\Persistence\ObjectRepository');
        $manager = $this->createMock('\Doctrine\Common\Persistence\ObjectManager');
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
