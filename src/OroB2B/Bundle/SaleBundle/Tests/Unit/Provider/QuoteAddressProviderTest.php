<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Provider;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;

class QuoteAddressProviderTest extends AbstractQuoteAddressProviderTest
{
    /**
     * @dataProvider quoteAccountAddressPermissions
     * @param string $type
     * @param string $expectedPermission
     * @param object $loggedUser
     */
    public function testGetAccountAddressesNotGranted($type, $expectedPermission, $loggedUser)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $this->securityFacade->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        [$expectedPermission, null, false],
                        ['VIEW;entity:' . $this->accountAddressClass, null, false],
                    ]
                )
            );

        $repository = $this->assertAccountAddressRepositoryCall();
        $repository->expects($this->never())
            ->method($this->anything());

        $this->provider->getAccountAddresses(new Account(), $type);

        // cache
        $this->provider->getAccountAddresses(new Account(), $type);
    }

    /**
     * @dataProvider quoteAccountAddressPermissions
     * @param string $type
     * @param string $expectedPermission
     * @param object $loggedUser
     */
    public function testGetAccountAddressesGrantedAny($type, $expectedPermission, $loggedUser)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $account = new Account();
        $addresses = [new AccountAddress()];

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($expectedPermission)
            ->willReturn(true);

        $repository = $this->assertAccountAddressRepositoryCall();
        $repository->expects($this->once())
            ->method('getAddressesByType')
            ->with($account, $type, $this->aclHelper)
            ->will($this->returnValue($addresses));

        $this->assertEquals($addresses, $this->provider->getAccountAddresses($account, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getAccountAddresses($account, $type));
    }

    /**
     * @dataProvider quoteAccountAddressPermissions
     * @param string $type
     * @param string $expectedPermission
     * @param object $loggedUser
     */
    public function testGetAccountAddressesGrantedView($type, $expectedPermission, $loggedUser)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $account = new Account();
        $addresses = [new AccountAddress()];

        $this->securityFacade->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        [$expectedPermission, null, false],
                        ['VIEW;entity:' . $this->accountAddressClass, null, true],
                    ]
                )
            );

        $repository = $this->assertAccountAddressRepositoryCall();
        $repository->expects($this->never())
            ->method('getAddressesByType');

        $repository->expects($this->once())
            ->method('getDefaultAddressesByType')
            ->with($account, $type, $this->aclHelper)
            ->will($this->returnValue($addresses));

        $this->assertEquals($addresses, $this->provider->getAccountAddresses($account, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getAccountAddresses($account, $type));
    }

    /**
     * @dataProvider accountUserAddressPermissions
     * @param string $type
     * @param array $expectedCalledPermissions
     * @param string $calledRepositoryMethod
     * @param array $addresses
     * @param object $loggedUser
     */
    public function testGetAccountUserAddresses(
        $type,
        array $expectedCalledPermissions,
        $calledRepositoryMethod,
        array $addresses,
        $loggedUser
    ) {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $accountUser = new AccountUser();

        $permissionsValueMap = [];
        foreach ($expectedCalledPermissions as $permission => $decision) {
            $permissionsValueMap[] = [$permission, null, $decision];
        }

        $this->securityFacade->expects($this->exactly(count($expectedCalledPermissions)))
            ->method('isGranted')
            ->will($this->returnValueMap($permissionsValueMap));

        $repository = $this->assertAccountUserAddressRepositoryCall();
        if ($calledRepositoryMethod) {
            $repository->expects($this->once())
                ->method($calledRepositoryMethod)
                ->with($accountUser, $type, $this->aclHelper)
                ->will($this->returnValue($addresses));
        } else {
            $repository->expects($this->never())
                ->method($this->anything());
        }

        $this->assertEquals($addresses, $this->provider->getAccountUserAddresses($accountUser, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getAccountUserAddresses($accountUser, $type));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function accountUserAddressPermissions()
    {
        return [
            [
                'shipping',
                [
                    'orob2b_quote_address_shipping_account_user_use_any' => false,
                    'orob2b_quote_address_shipping_account_user_use_default' => false,
                ],
                null,
                [],
                new AccountUser()
            ],
            [
                'shipping',
                [
                    'orob2b_quote_address_shipping_account_user_use_any' => true
                ],
                'getAddressesByType',
                [new AccountUserAddress()],
                new AccountUser()
            ],
            [
                'shipping',
                [
                    'orob2b_quote_address_shipping_account_user_use_any' => false,
                    'orob2b_quote_address_shipping_account_user_use_default' => true
                ],
                'getDefaultAddressesByType',
                [new AccountUserAddress()],
                new AccountUser()
            ],
            [
                'shipping',
                [
                    'orob2b_quote_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_quote_address_shipping_account_user_use_default_backend' => false,
                ],
                null,
                [],
                new \stdClass()
            ],
            [
                'shipping',
                [
                    'orob2b_quote_address_shipping_account_user_use_any_backend' => true
                ],
                'getAddressesByType',
                [new AccountUserAddress()],
                new \stdClass()
            ],
            [
                'shipping',
                [
                    'orob2b_quote_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_quote_address_shipping_account_user_use_default_backend' => true
                ],
                'getDefaultAddressesByType',
                [new AccountUserAddress()],
                new \stdClass()
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertAccountAddressRepositoryCall()
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Repository\AccountAddressRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($this->accountAddressClass)
            ->will($this->returnValue($repository));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->accountAddressClass)
            ->will($this->returnValue($manager));

        return $repository;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertAccountUserAddressRepositoryCall()
    {
        $repository = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserAddressRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($this->accountUserAddressClass)
            ->will($this->returnValue($repository));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->accountUserAddressClass)
            ->will($this->returnValue($manager));

        return $repository;
    }
}
