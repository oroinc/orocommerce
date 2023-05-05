<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Manager;

use Oro\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Component\Testing\ReflectionUtil;

class TypedOrderAddressCollectionTest extends \PHPUnit\Framework\TestCase
{
    private const TYPE = 'test_type';
    private const ID = 42;

    public function testToArray()
    {
        $expected = ['data'];

        $collection = new TypedOrderAddressCollection(new CustomerUser(), self::TYPE, $expected);

        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * @dataProvider getDefaultAddressKeyDataProvider
     */
    public function testGetDefaultAddressKey(array $addresses, ?string $expected)
    {
        $collection = new TypedOrderAddressCollection($this->getCustomerUser(self::ID), self::TYPE, $addresses);

        $this->assertEquals($expected, $collection->getDefaultAddressKey());
    }

    public function getDefaultAddressKeyDataProvider(): array
    {
        $expected = 'identifier';
        $customerUser = $this->getCustomerUser(self::ID);

        return [
            'empty addresses' => [
                'addresses' => [],
                'expected' => null
            ],
            'without default address' => [
                'addresses' => [
                    'group1' => ['id1' => $this->getAddress(new Customer(), 'unknown')],
                    'group2' => ['id2' => $this->getAddress(new CustomerUser(), 'unknown')],
                    'group3' => ['id3' => $this->getAddress($customerUser, 'unknown')]
                ],
                'expected' => 'id1'
            ],
            'default address' => [
                'addresses' => [
                    'group1' => ['id1' => $this->getAddress(new Customer(), self::TYPE)],
                    'group2' => [$expected => $this->getAddress(new CustomerUser(), self::TYPE)],
                    'group3' => ['id3' => $this->getAddress($customerUser, 'unknown')]
                ],
                'expected' => $expected
            ],
            'default address for customer user with id' => [
                'addresses' => [
                    'group1' => ['id1' => $this->getAddress(new Customer(), self::TYPE)],
                    'group2' => ['id2' => $this->getAddress(new CustomerUser(), self::TYPE)],
                    'group3' => [
                        $expected => $this->getAddress($customerUser, self::TYPE),
                        'id3' => $this->getAddress($customerUser, 'unknown')
                    ]
                ],
                'expected' => $expected
            ],
            'default address for customer user with id and user address' => [
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
            ]
        ];
    }

    /**
     * @dataProvider getDefaultAddressDataProvider
     */
    public function testGetDefaultAddress(array $addresses, ?AbstractDefaultTypedAddress $expected)
    {
        $collection = new TypedOrderAddressCollection($this->getCustomerUser(self::ID), self::TYPE, $addresses);

        $this->assertEquals($expected, $collection->getDefaultAddress());
    }

    public function getDefaultAddressDataProvider(): array
    {
        $customerUser = $this->getCustomerUser(self::ID);

        return [
            'empty addresses' => [
                'addresses' => [],
                'expected' => null
            ],
            'without default address' => [
                'addresses' => [
                    'group1' => ['id1' => $this->getAddress(new Customer(), 'unknown')],
                    'group2' => ['id2' => $this->getAddress(new CustomerUser(), 'unknown')],
                    'group3' => ['id3' => $this->getAddress($customerUser, 'unknown')]
                ],
                'expected' => $this->getAddress(new Customer(), 'unknown')
            ],
            'default address' => [
                'addresses' => [
                    'group1' => ['id1' => $this->getAddress(new Customer(), self::TYPE)],
                    'group2' => ['id2' => $this->getAddress(new CustomerUser(), self::TYPE)],
                    'group3' => ['id3' => $this->getAddress($customerUser, 'unknown')]
                ],
                'expected' => $this->getAddress(new CustomerUser(), self::TYPE)
            ],
            'default address for customer user with id' => [
                'addresses' => [
                    'group1' => ['id1' => $this->getAddress(new Customer(), self::TYPE)],
                    'group2' => ['id2' => $this->getAddress(new CustomerUser(), self::TYPE)],
                    'group3' => [
                        'id3' => $this->getAddress($customerUser, self::TYPE),
                        'id4' => $this->getAddress($customerUser, 'unknown')
                    ]
                ],
                'expected' => $this->getAddress($customerUser, self::TYPE)
            ],
            'default address for customer user with id and user address' => [
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
            ]
        ];
    }

    private function getCustomerUser(int $id): CustomerUser
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, $id);

        return $customerUser;
    }

    private function getAddress(
        object $frontendOwner,
        string $type,
        ?string $class = null
    ): AbstractDefaultTypedAddress {
        $address = $this->createMock($class ?: AbstractDefaultTypedAddress::class);
        $address->expects($this->any())
            ->method('getFrontendOwner')
            ->willReturn($frontendOwner);
        $address->expects($this->any())
            ->method('hasDefault')
            ->willReturnCallback(function ($param) use ($type) {
                return $param === $type;
            });

        return $address;
    }
}
