<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Model;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
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

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderAddressProvider */
    protected $orderAddressProvider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAddressProvider = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Provider\OrderAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new OrderAddressSecurityProvider(
            $this->securityFacade,
            $this->orderAddressProvider,
            self::ACCOUNT_ORDER_CLASS,
            self::ACCOUNT_USER_ORDER_CLASS
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade, $this->provider, $this->orderAddressProvider);
    }

    /**
     * @dataProvider manualEditDataProvider
     * @param string $type
     * @param string $permissionName
     * @param bool $permission
     */
    public function testIsManualEditGranted($type, $permissionName, $permission)
    {
        $this->securityFacade->expects($this->atLeastOnce())->method('isGranted')->with($permissionName)
            ->willReturn($permission);

        $this->assertEquals($permission, $this->provider->isManualEditGranted($type));
    }

    /**
     * @return array
     */
    public function manualEditDataProvider()
    {
        return [
            ['billing', 'orob2b_order_address_billing_allow_manual_backend', true],
            ['billing', 'orob2b_order_address_billing_allow_manual_backend', false],
            ['shipping', 'orob2b_order_address_shipping_allow_manual_backend', true],
            ['shipping', 'orob2b_order_address_shipping_allow_manual_backend', false],
        ];
    }

    /**
     * @param bool $isGrantedViewAccount
     * @param bool $isGrantedViewAccountUser
     * @param bool $isGrantedUseAnyAccountUser
     * @param bool $isAddressGranted
     * @param bool $isAccountAddressGranted
     * @param bool $isAccountUserAddressGranted
     * @param bool $isManualEditGranted
     * @param bool $hasAccountUserAddresses
     * @param bool $hasAccountAddresses
     * @param bool $hasEntity
     *
     * @dataProvider permissionsDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testIsAddressGranted(
        $isGrantedViewAccount,
        $isGrantedViewAccountUser,
        $isGrantedUseAnyAccountUser,
        $isAddressGranted,
        $isAccountAddressGranted,
        $isAccountUserAddressGranted,
        $isManualEditGranted = true,
        $hasAccountUserAddresses = true,
        $hasAccountAddresses = true,
        $hasEntity = true
    ) {
        $this->orderAddressProvider->expects($this->any())->method('getAccountAddresses')
            ->willReturn($hasAccountAddresses);
        $this->orderAddressProvider->expects($this->any())->method('getAccountUserAddresses')
            ->willReturn($hasAccountUserAddresses);

        $this->securityFacade->expects($this->any())->method('getLoggedUser')->willReturn(new \stdClass());
        $this->securityFacade->expects($this->atLeastOnce())->method('isGrantedClassPermission')
            ->with($this->isType('string'), $this->isType('string'))->will(
                $this->returnValueMap(
                    [
                        ['VIEW', self::ACCOUNT_ORDER_CLASS, $isGrantedViewAccount],
                        ['VIEW', self::ACCOUNT_USER_ORDER_CLASS, $isGrantedViewAccountUser],
                    ]
                )
            );

        $this->securityFacade->expects($this->any())->method('isGranted')->with($this->isType('string'))
            ->will(
                $this->returnValueMap(
                    [
                        ['orob2b_order_address_shipping_allow_manual_backend', null, $isManualEditGranted],
                        ['orob2b_order_address_billing_allow_manual_backend', null, $isManualEditGranted],
                        [
                            OrderAddressProvider::ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY,
                            null,
                            $isGrantedUseAnyAccountUser,
                        ],
                        [OrderAddressProvider::ADDRESS_BILLING_ACCOUNT_USER_USE_ANY, null, $isGrantedUseAnyAccountUser],
                    ]
                )
            );

        $order = null;
        $account = null;
        $accountUser = null;
        if ($hasEntity) {
            $account = new Account();
            $accountUser = new AccountUser();
        }
        $order = (new Order())->setAccount($account)->setAccountUser($accountUser);

        $this->assertEquals(
            $isAddressGranted,
            $this->provider->isAddressGranted($order, AddressType::TYPE_BILLING)
        );
        $this->assertEquals(
            $isAddressGranted,
            $this->provider->isAddressGranted($order, AddressType::TYPE_SHIPPING)
        );
        $this->assertEquals(
            $isAccountAddressGranted,
            $this->provider->isAccountAddressGranted(AddressType::TYPE_BILLING, $account)
        );
        $this->assertEquals(
            $isAccountAddressGranted,
            $this->provider->isAccountAddressGranted(AddressType::TYPE_SHIPPING, $account)
        );
        $this->assertEquals(
            $isAccountUserAddressGranted,
            $this->provider->isAccountUserAddressGranted(AddressType::TYPE_BILLING, $accountUser)
        );
        $this->assertEquals(
            $isAccountUserAddressGranted,
            $this->provider->isAccountUserAddressGranted(AddressType::TYPE_SHIPPING, $accountUser)
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                'isAccountUserAddressGranted' => false,
            ],
            'view account user address granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => false,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => false,
            ],
            'use account user address granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => false,
            ],
            'account user address granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => true,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => true,
            ],
            'account address granted account user address denied' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => false,
                'isAddressGranted' => true,
                'isAccountAddressGranted' => true,
                'isAccountUserAddressGranted' => false,
            ],
            'granted' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => true,
                'isAccountAddressGranted' => true,
                'isAccountUserAddressGranted' => true,
            ],
            'not granted manual edit' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => false,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => false,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => false,
                'hasAccountAddresses' => false,
            ],
            'view account user address granted without manual permission granted' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => false,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => false,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => false,
                'hasAccountAddresses' => false,
            ],
            'use account user address granted without manual permission' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => false,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => false,
                'hasAccountAddresses' => false,
            ],
            'account user address not granted without manual permission' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => false,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => false,
                'hasAccountAddresses' => false,
            ],
            'account user address granted without manual permission' => [
                'isGrantedViewAccount' => false,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => true,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => true,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => true,
                'hasAccountAddresses' => false,
            ],
            'account address granted account user address granted without manual permission' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => false,
                'isAddressGranted' => true,
                'isAccountAddressGranted' => true,
                'isAccountUserAddressGranted' => false,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => false,
                'hasAccountAddresses' => true,
            ],
            'account address granted account user address denied without manual permission' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => false,
                'isGrantedUseAnyAccountUser' => false,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => false,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => false,
                'hasAccountAddresses' => false,
            ],
            'denied without manual permission' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => false,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => false,
                'hasAccountAddresses' => false,
            ],
            'granted without manual permission with account addresses' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => true,
                'isAccountAddressGranted' => true,
                'isAccountUserAddressGranted' => false,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => false,
                'hasAccountAddresses' => true,
            ],
            'granted without manual permission with account user addresses' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => true,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => true,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => true,
                'hasAccountAddresses' => false,
            ],
            'denied if no account' => [
                'isGrantedViewAccount' => true,
                'isGrantedViewAccountUser' => true,
                'isGrantedUseAnyAccountUser' => true,
                'isAddressGranted' => false,
                'isAccountAddressGranted' => false,
                'isAccountUserAddressGranted' => false,
                'isManualEditGranted' => false,
                'hasAccountUserAddresses' => true,
                'hasAccountAddresses' => true,
                'hasEntity' => false,
            ],
        ];
    }
}
