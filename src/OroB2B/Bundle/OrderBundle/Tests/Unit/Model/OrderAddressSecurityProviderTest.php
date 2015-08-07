<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Model;

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
     * @param bool $isGrantedViewAccount
     * @param bool $isGrantedViewAccountUser
     * @param bool $isGrantedUseAnyAccountUser
     * @param bool $isShippingAddressGranted
     * @param bool $isShippingAccountAddressGranted
     * @param bool $isShippingAccountUserAddressGranted
     *
     * @dataProvider permissionsDataProvider
     */
    public function testIsShippingAddressGranted(
        $isGrantedViewAccount,
        $isGrantedViewAccountUser,
        $isGrantedUseAnyAccountUser,
        $isShippingAddressGranted,
        $isShippingAccountAddressGranted,
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
                    ]
                )
            );

        $this->assertEquals($isShippingAddressGranted, $this->provider->isShippingAddressGranted());
        $this->assertEquals($isShippingAccountAddressGranted, $this->provider->isShippingAccountAddressGranted());
        $this->assertEquals(
            $isShippingAccountUserAddressGranted,
            $this->provider->isShippingAccountUserAddressGranted()
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
                'isShippingAddressGranted' => false,
                'isShippingAccountAddressGranted' => false,
                'isShippingAccountUserAddressGranted' => false,
            ],
            'view account user address granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => false,
                'isShippingAddressGranted' => false,
                'isShippingAccountAddressGranted' => false,
                'isShippingAccountUserAddressGranted' => false,
            ],
            'use account user address granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => true,
                'isShippingAddressGranted' => false,
                'isShippingAccountAddressGranted' => false,
                'isShippingAccountUserAddressGranted' => false,
            ],
            'account user address granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isShippingAddressGranted' => true,
                'isShippingAccountAddressGranted' => false,
                'isShippingAccountUserAddressGranted' => true,
            ],
            'account address granted account user address denied' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => false,
                'isShippingAddressGranted' => true,
                'isShippingAccountAddressGranted' => true,
                'isShippingAccountUserAddressGranted' => false,
            ],
            'granted' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isShippingAddressGranted' => true,
                'isShippingAccountAddressGranted' => true,
                'isShippingAccountUserAddressGranted' => true,
            ],
        ];
    }
}
