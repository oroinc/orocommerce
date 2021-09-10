<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Manager;

use Oro\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Component\Testing\Unit\EntityTrait;

class TypedOrderAddressCollectionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const TYPE = 'test_type';
    const ID = 42;

    public function testToArray()
    {
        $expected = ['data'];

        $collection = new TypedOrderAddressCollection($this->getCustomerUser(), self::TYPE, $expected);

        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * @dataProvider getDefaultAddressKeyDataProvider
     *
     * @param array $addresses
     * @param int|null $expected
     */
    public function testGetDefaultAddressKey(array $addresses, $expected)
    {
        $collection = new TypedOrderAddressCollection($this->getCustomerUser(self::ID), self::TYPE, $addresses);

        $this->assertEquals($expected, $collection->getDefaultAddressKey());
    }

    /**
     * @return \Generator
     */
    public function getDefaultAddressKeyDataProvider()
    {
        $expected = 'identifier';
        $customerUser = $this->getCustomerUser(self::ID);

        yield 'empty addresses' => [
            'addresses' => [],
            'expected' => null
        ];

        yield 'without default address' => [
            'addresses' => [
                'group1' => ['id1' => $this->getAddress(new Customer(), 'unknown')],
                'group2' => ['id2' => $this->getAddress(new CustomerUser(), 'unknown')],
                'group3' => ['id3' => $this->getAddress($customerUser, 'unknown')]
            ],
            'expected' => 'id1'
        ];

        yield 'default address' => [
            'addresses' => [
                'group1' => ['id1' => $this->getAddress(new Customer(), self::TYPE)],
                'group2' => [$expected => $this->getAddress(new CustomerUser(), self::TYPE)],
                'group3' => ['id3' => $this->getAddress($customerUser, 'unknown')]
            ],
            'expected' => $expected
        ];

        yield 'default address for customer user with id' => [
            'addresses' => [
                'group1' => ['id1' => $this->getAddress(new Customer(), self::TYPE)],
                'group2' => ['id2' => $this->getAddress(new CustomerUser(), self::TYPE)],
                'group3' => [
                    $expected => $this->getAddress($customerUser, self::TYPE),
                    'id3' => $this->getAddress($customerUser, 'unknown')
                ]
            ],
            'expected' => $expected
        ];

        yield 'default address for customer user with id and user address' => [
            'addresses' => [
                'group1' => ['id1' => $this->getAddress(new Customer(), self::TYPE)],
                'group2' => ['id2' => $this->getAddress(new CustomerUser(), self::TYPE)],
                'group3' => [
                    'id3' => $this->getAddress($customerUser, 'unknown'),
                    $expected => $this->getAddress($customerUser, self::TYPE, CustomerUserAddress::class),
                    'id4' => $this->getAddress($customerUser, self::TYPE)
                ]
            ],
            'expected' => $expected
        ];
    }

    /**
     * @dataProvider getDefaultAddressDataProvider
     */
    public function testGetDefaultAddress(array $addresses, AbstractDefaultTypedAddress $expected = null)
    {
        $collection = new TypedOrderAddressCollection($this->getCustomerUser(self::ID), self::TYPE, $addresses);

        $this->assertEquals($expected, $collection->getDefaultAddress());
    }

    /**
     * @return \Generator
     */
    public function getDefaultAddressDataProvider()
    {
        $customerUser = $this->getCustomerUser(self::ID);

        yield 'empty addresses' => [
            'addresses' => [],
            'expected' => null
        ];

        yield 'without default address' => [
            'addresses' => [
                'group1' => ['id1' => $this->getAddress(new Customer(), 'unknown')],
                'group2' => ['id2' => $this->getAddress(new CustomerUser(), 'unknown')],
                'group3' => ['id3' => $this->getAddress($customerUser, 'unknown')]
            ],
            'expected' => $this->getAddress(new Customer(), 'unknown')
        ];

        yield 'default address' => [
            'addresses' => [
                'group1' => ['id1' => $this->getAddress(new Customer(), self::TYPE)],
                'group2' => ['id2' => $this->getAddress(new CustomerUser(), self::TYPE)],
                'group3' => ['id3' => $this->getAddress($customerUser, 'unknown')]
            ],
            'expected' => $this->getAddress(new CustomerUser(), self::TYPE)
        ];

        yield 'default address for customer user with id' => [
            'addresses' => [
                'group1' => ['id1' => $this->getAddress(new Customer(), self::TYPE)],
                'group2' => ['id2' => $this->getAddress(new CustomerUser(), self::TYPE)],
                'group3' => [
                    'id3' => $this->getAddress($customerUser, self::TYPE),
                    'id4' => $this->getAddress($customerUser, 'unknown')
                ]
            ],
            'expected' => $this->getAddress($customerUser, self::TYPE)
        ];

        yield 'default address for customer user with id and user address' => [
            'addresses' => [
                'group1' => ['id1' => $this->getAddress(new Customer(), self::TYPE)],
                'group2' => ['id2' => $this->getAddress(new CustomerUser(), self::TYPE)],
                'group3' => [
                    'id3' => $this->getAddress($customerUser, 'unknown'),
                    'id4' => $this->getAddress($customerUser, self::TYPE, CustomerUserAddress::class),
                    'id5' => $this->getAddress($customerUser, self::TYPE)
                ]
            ],
            'expected' => $this->getAddress($customerUser, self::TYPE, CustomerUserAddress::class)
        ];
    }

    /**
     * @param int $id
     * @return CustomerUser
     */
    protected function getCustomerUser($id = null)
    {
        return $this->getEntity(CustomerUser::class, ['id' => $id]);
    }

    /**
     * @param object $frontendOwner
     * @param string $type
     * @param string|null $class
     * @return AbstractDefaultTypedAddress
     */
    protected function getAddress($frontendOwner, $type, $class = null)
    {
        /** @var AbstractDefaultTypedAddress|\PHPUnit\Framework\MockObject\MockObject $address */
        $address = $this->createMock($class ?: AbstractDefaultTypedAddress::class);
        $address->expects($this->any())
            ->method('getFrontendOwner')
            ->willReturn($frontendOwner);

        $address->expects($this->any())
            ->method('hasDefault')
            ->willReturnCallback(
                function ($param) use ($type) {
                    return $param === $type;
                }
            );

        return $address;
    }
}
