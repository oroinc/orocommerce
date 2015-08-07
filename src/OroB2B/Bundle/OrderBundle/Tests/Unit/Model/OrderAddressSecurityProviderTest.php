<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Model;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\OrderBundle\Provider\OrderAddressProvider;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;

class OrderAddressSecurityProviderTest extends \PHPUnit_Framework_TestCase
{
    const ACCOUNT_ORDER_CLASS = 'AccountOrderClass';
    const ACCOUNT_USER_ORDER_CLASS = 'AccountUserOrderClass';

    /** @var OrderAddressSecurityProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new OrderAddressSecurityProvider(
            $this->securityFacade,
            self::ACCOUNT_ORDER_CLASS,
            self::ACCOUNT_USER_ORDER_CLASS
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade, $this->provider);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected "shipping" or "billing", "wrong" given
     */
    public function testWrongType()
    {
        $this->securityFacade->expects($this->atLeastOnce())->method('isGrantedClassPermission')->willReturn(true);
        $this->provider->isAccountUserAddressGranted('wrong');
    }

    /**
     * @param bool $isGrantedViewAccount
     * @param bool $isGrantedViewAccountUser
     * @param bool $isGrantedUseAnyAccountUser
     * @param bool $isAddressGranted
     * @param bool $isAccountAddressGranted
     * @param bool $isShippingAccountUserAddressGranted
     *
     * @dataProvider permissionsDataProvider
     */
    public function testIsAddressGranted(
        $isGrantedViewAccount,
        $isGrantedViewAccountUser,
        $isGrantedUseAnyAccountUser,
        $isAddressGranted,
        $isAccountAddressGranted,
        $isShippingAccountUserAddressGranted
    ) {
        $this->securityFacade->expects($this->atLeastOnce())->method('isGrantedClassPermission')
            ->with($this->isType('string'), $this->isType('string'))->will(
                $this->returnValueMap(
                    [
                        [
                            'VIEW',
                            self::ACCOUNT_ORDER_CLASS,
                            $isGrantedViewAccount,
                        ],
                        [
                            'VIEW',
                            self::ACCOUNT_USER_ORDER_CLASS,
                            $isGrantedViewAccountUser,
                        ],
                        [
                            OrderAddressProvider::ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY,
                            self::ACCOUNT_USER_ORDER_CLASS,
                            $isGrantedUseAnyAccountUser,
                        ],
                        [
                            OrderAddressProvider::ADDRESS_BILLING_ACCOUNT_USER_USE_ANY,
                            self::ACCOUNT_USER_ORDER_CLASS,
                            $isGrantedUseAnyAccountUser,
                        ],
                    ]
                )
            );

        $this->assertEquals($isAddressGranted, $this->provider->isAddressGranted(AddressType::TYPE_BILLING));
        $this->assertEquals($isAddressGranted, $this->provider->isAddressGranted(AddressType::TYPE_SHIPPING));
        $this->assertEquals(
            $isAccountAddressGranted,
            $this->provider->isAccountAddressGranted(AddressType::TYPE_BILLING)
        );
        $this->assertEquals(
            $isAccountAddressGranted,
            $this->provider->isAccountAddressGranted(AddressType::TYPE_SHIPPING)
        );
        $this->assertEquals(
            $isShippingAccountUserAddressGranted,
            $this->provider->isAccountUserAddressGranted(AddressType::TYPE_BILLING)
        );
        $this->assertEquals(
            $isShippingAccountUserAddressGranted,
            $this->provider->isAccountUserAddressGranted(AddressType::TYPE_SHIPPING)
        );
    }

    /**
     * @return array
     */
    public function permissionsDataProvider()
    {
        return [
            'not granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => false,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isShippingAccountUserAddressGranted' => false,
            ],
            'view account user address granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => false,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isShippingAccountUserAddressGranted' => false,
            ],
            'use account user address granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isShippingAccountUserAddressGranted' => false,
            ],
            'account user address granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => true,
                'isAccountAddressGranted' => false,
                'isShippingAccountUserAddressGranted' => true,
            ],
            'account address granted account user address denied' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => false,
                'isAddressGranted' => true,
                'isAccountAddressGranted' => true,
                'isShippingAccountUserAddressGranted' => false,
            ],
            'granted' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => true,
                'isAccountAddressGranted' => true,
                'isShippingAccountUserAddressGranted' => true,
            ],
        ];
    }
}
