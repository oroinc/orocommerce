<?php

namespace Oro\Bundle\CustomerBundle\Tests\Selenium;

use Oro\Bundle\CustomerBundle\Tests\Selenium\Cache\AddressBookCache;
use Oro\Bundle\CustomerBundle\Tests\Selenium\Entity\SeleniumCustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Selenium\Entity\SeleniumCustomerUserTestRole;
use Oro\Bundle\CustomerBundle\Tests\Selenium\Entity\SeleniumAddress;
use Oro\Bundle\CustomerBundle\Tests\Selenium\Pages\AdminCustomerAddressPages;
use Oro\Bundle\CustomerBundle\Tests\Selenium\Pages\CustomerAdminPages;
use Oro\Bundle\CustomerBundle\Tests\Selenium\Pages\AddAddressPage;
use Oro\Bundle\CustomerBundle\Tests\Selenium\Pages\AddressBookTestPage;
use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class AddressBookTest extends Selenium2TestCase
{
    // View/create/edit/delete customer address and customer user address
    const ROLE1 = 'ROLE__VCED-AC_AD-VCED-ACU_AD';
    const ROLE2 = 'ROLE__VED-AC_AD-VED-ACU_AD';
    const ROLE3 = 'ROLE__VE-AC_AD-VE-ACU_AD';
    const ROLE4 = 'ROLE__V-AC_AD-V-ACU_AD';

    const USER1 = 'user1@test.com';
    const USER2 = 'user2@test.com';
    const USER3 = 'user3@test.com';
    const USER4 = 'user4@test.com';

    const ADDRESS_LIST_LIMIT = 8;

    protected static $gridHeaders = ["Address", "City", "State", "Zip/Postal Code", "Country", "Type"];

    public function testInit()
    {
        $this->markTestSkipped("Skipped until task BB-4263 gets resolved!");
        $page = $this->getAddressBookPage();
        $page->loginAdmin();

        $customerPage = $this->getCustomerAdminPage();
        $customerPage->createCustomerUserRoles($this->getRoles());

        AddressBookCache::$usersInfo = $customerPage->createCustomerUsersWithRoles($this->getCustomerUsers());

        // add one default address for each user
        $customerAddressPage = $this->getCustomerAddressPage();
        foreach (AddressBookCache::$usersInfo as $userInfo) {
            $customerAddressPage->addCustomerUserAddress($userInfo['userId'], 1);
        }
    }

    /**
     * @param SeleniumCustomerUser $frontendUser
     * @param bool $usrAddrIsGrid           Customer user address should be in grid on not (list)
     * @param bool $accAddrIsGrid           Customer address should be in grid on not (list)
     * @param bool $usrAddrHasAddBtn        Customer user address should have add address button
     * @param bool $accAddrHasAddBtn        Customer address should have add address button
     * @param bool $usrAddrHasEditButtons   Customer user address should have edit address button
     * @param bool $accAddrHasEditBtn       Customer address should have edit address button
     * @param bool $usrAddrHasDeleteButtons Customer user address should have delete address button
     * @param bool $accAddrHasDeleteBtn     Customer address should have delete address button
     * @param int $nrOfUsrAddr              Number of expected customer users addresses
     * @param int $nrOfAccAddr              Number of expected customer addresses
     * @param int $nrOfUsrAddrToCreate      Number of customer user addresses that should be created
     * @param int $nrOfAccAddrToCreate      Number of customer addresses that should be created
     * @param int $nrOfUsrAddrToDelete      Number of customer user addresses that should be deleted
     * @param int $nrOfAccAddrToDelete      Number of customer addresses that should be deleted
     *
     * @throws \Throwable
     * @dataProvider getAddressBookTestProvider
     * @depends      testInit
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testAddressBook(
        SeleniumCustomerUser $frontendUser,
        $usrAddrIsGrid,
        $accAddrIsGrid,
        $usrAddrHasAddBtn,
        $accAddrHasAddBtn,
        $usrAddrHasEditButtons,
        $accAddrHasEditBtn,
        $usrAddrHasDeleteButtons,
        $accAddrHasDeleteBtn,
        $nrOfUsrAddr,
        $nrOfAccAddr,
        $nrOfUsrAddrToCreate = 0,
        $nrOfAccAddrToCreate = 0,
        $nrOfUsrAddrToDelete = 0,
        $nrOfAccAddrToDelete = 0
    ) {
        $this->markTestSkipped("Skipped until task BB-4263 gets resolved!");
        $frontendUserInfo = $this->getUsersInfo()[$frontendUser->email];
        $usrAddrGridClass = $usrAddrIsGrid ? 'oro-grid__row' : 'address-list';
        $accAddrGridClass = $accAddrIsGrid ? 'oro-grid__row' : 'address-list';
        $nrOfUsrAddrAddBtn = $usrAddrHasAddBtn ? ($usrAddrIsGrid ? 2 : 1) : 0;
        $nrOfAccAddrAddBtn = $accAddrHasAddBtn ? ($accAddrIsGrid ? 2 : 1) : 0;

        $page = $this->getAddressBookPage();
        $addressPage = $this->getCustomerAddressPage();
        if ($nrOfUsrAddrToCreate > 0
            || $nrOfAccAddrToCreate > 0
            || $nrOfUsrAddrToDelete > 0
            || $nrOfAccAddrToDelete > 0
        ) {
            $page->loginAdmin();
        }

        if ($nrOfUsrAddrToCreate > 0 || $nrOfAccAddrToCreate > 0) {
            $this->createAddresses($addressPage, $frontendUserInfo, $nrOfUsrAddrToCreate, $nrOfAccAddrToCreate);
        }

        try {
            $page->login($frontendUser->email, $frontendUser->password);
            $page->getTest()->url(AddressBookTestPage::ADDRESS_BOOK_URL);
            $page->waitPageToLoad();
            $page->waitForAjax();

            // Customer User Address section assertions
            $this->assertGridColumns($page, $usrAddrIsGrid, AddressBookTestPage::USER_ADDRESS_BLOCK_SELECTOR);

            $this->assertContains($usrAddrGridClass, $page->getUserAddressBlock()->attribute('class'));
            $this->assertButtons($page, $usrAddrIsGrid, 'showOnMap', 'user', $usrAddrIsGrid, $nrOfUsrAddr);
            $this->assertButtons($page, $usrAddrHasAddBtn, 'add', 'user', $usrAddrIsGrid, $nrOfUsrAddrAddBtn);
            $this->assertButtons($page, $usrAddrHasEditButtons, 'edit', 'user', $usrAddrIsGrid, $nrOfUsrAddr);
            $this->assertButtons($page, $usrAddrHasDeleteButtons, 'delete', 'user', $usrAddrIsGrid, $nrOfUsrAddr);

            // Customer Address section assertions
            $this->assertGridColumns($page, $accAddrIsGrid, AddressBookTestPage::ACCOUNT_ADDRESS_BLOCK_SELECTOR);
            $this->assertContains($accAddrGridClass, $page->getCustomerAddressBlock()->attribute('class'));
            $this->assertButtons($page, $usrAddrIsGrid, 'showOnMap', 'customer', $accAddrIsGrid, $nrOfAccAddr);
            $this->assertButtons($page, $accAddrHasAddBtn, 'add', 'customer', $accAddrIsGrid, $nrOfAccAddrAddBtn);
            $this->assertButtons($page, $accAddrHasEditBtn, 'edit', 'customer', $accAddrIsGrid, $nrOfAccAddr);
            $this->assertButtons($page, $accAddrHasDeleteBtn, 'delete', 'customer', $accAddrIsGrid, $nrOfAccAddr);
        } catch (\Throwable $e) {
            // making sure fixtures are also deleted in case of test failure
            $this->cleanupAddresses($addressPage, $frontendUserInfo, $nrOfUsrAddrToDelete, $nrOfAccAddrToDelete);

            throw $e;
        }

        $this->cleanupAddresses($addressPage, $frontendUserInfo, $nrOfUsrAddrToDelete, $nrOfAccAddrToDelete);
    }

    /**
     * @return array
     */
    public function getAddressBookTestProvider()
    {
        return [
            [$this->getCustomerUsers(self::USER1), false, false, true, true, true, true, true, true, 1, 1],
            [$this->getCustomerUsers(self::USER2), false, false, false, false, true, true, true, true, 1, 1],
            [$this->getCustomerUsers(self::USER3), false, false, false, false, true, true, false, false, 1, 1],
            [$this->getCustomerUsers(self::USER4), false, false, false, false, false, false, false, false, 1, 1],
            [$this->getCustomerUsers(self::USER1), true, true, true, true, true, true, true, true, 9, 9, 8, 8, 8, 0],
            [$this->getCustomerUsers(self::USER2), true, true, false, false, true, true, true, true, 9, 9, 8, 0, 8, 0],
            [
                $this->getCustomerUsers(self::USER3),
                true,
                true,
                false,
                false,
                true,
                true,
                false,
                false,
                9,
                9,
                8,
                0,
                8,
                0
            ],
            [
                $this->getCustomerUsers(self::USER4),
                true,
                true,
                false,
                false,
                false,
                false,
                false,
                false,
                9,
                9,
                8,
                0,
                8,
                8,
            ],
        ];
    }

    /**
     * @depends testAddressBook
     */
    public function testNoAddresses()
    {
        $this->markTestSkipped("Skipped until task BB-4263 gets resolved!");
        $page = $this->getAddressBookPage();
        $user = $this->getCustomerUsers(self::USER1);
        $page->login($user->email, $user->password);

        $page->getTest()->url(AddressBookTestPage::ADDRESS_BOOK_URL);
        $page->waitPageToLoad();
        $page->deleteCustomerAddresses(false);
        $page->deleteUserAddresses(false);
        $page->getTest()->url(AddressBookTestPage::ADDRESS_BOOK_URL);
        $page->waitPageToLoad();
        $page->waitForAjax();
        $this->assertNotEmpty(
            $page->getElement(
                AddressBookTestPage::ACCOUNT_ADDRESS_BLOCK_SELECTOR . "//div[contains(@class, 'no-data')]"
            )
        );
        $this->assertNotEmpty(
            $page->getElement(
                AddressBookTestPage::USER_ADDRESS_BLOCK_SELECTOR . "//div[contains(@class, 'no-data')]"
            )
        );
    }

    /**
     * @depends testNoAddresses
     */
    public function testAddCustomerAddress()
    {
        $this->markTestSkipped("Skipped until task BB-4263 gets resolved!");
        $user = $this->getCustomerUsers(self::USER1);
        $addressPage = $this->getAddressBookPage();
        $addressPage->login($user->email, $user->password);

        $addAddressPage = $this->getAddAddressPage();
        $userInfo = $this->getUsersInfo()[$user->email];

        foreach ($this->getAddressesWithTypes() as $addressInfo) {
            $addAddressPage->openAddCustomerAddressPage($userInfo['customerId']);
            $addAddressPage->addNewAddress($addressInfo['address']);

            $types = $addressPage->getElement(
                AddressBookTestPage::ACCOUNT_ADDRESS_BLOCK_SELECTOR
                . "//li[1]/div[contains(@class, 'address-list__type')]"
            )->text();
            $types = explode("\n", $types);
            $this->assertEquals(sort($addressInfo['expectedTypes']), sort($types));

            if (!isset($addressInfo['deleteOnFinish']) || $addressInfo['deleteOnFinish']) {
                $addressPage->deleteCustomerAddresses(false);
            }
        }
    }

    /**
     * @depends testNoAddresses
     */
    public function testFinish()
    {
        $this->markTestSkipped("Skipped until task BB-4263 gets resolved!");
        $this->getAddressBookPage()->loginAdmin();
        $this->getCustomerAdminPage()
            ->deleteRoles($this->getRoles())
            ->deleteCustomerUsers($this->getCustomerUsers());
    }

    /**
     * @return array
     */
    protected function getAddressesWithTypes()
    {
        return [
            [
                'address' => new SeleniumAddress('Wall', 'New York', 'United States', 'New York', 123),
                'expectedTypes' => [''],
            ],
            [
                'address' => new SeleniumAddress('Wall', 'New York', 'United States', 'New York', 123, true),
                'expectedTypes' => ['Billing'],
            ],
            [
                'address' => new SeleniumAddress('Wall', 'New York', 'United States', 'New York', 123, false, true),
                'expectedTypes' => ['Shipping'],
            ],
            [
                'address' => new SeleniumAddress('Wall', 'New York', 'United States', 'New York', 123, true, true),
                'expectedTypes' => ['Billing', 'Shipping'],
            ],
            [
                'address' => new SeleniumAddress(
                    'Wall',
                    'New York',
                    'United States',
                    'New York',
                    123,
                    true,
                    false,
                    true
                ),
                'expectedTypes' => ['Billing', 'Default Shipping'],
            ],
            [
                'address' => new SeleniumAddress(
                    'Wall',
                    'New York',
                    'United States',
                    'New York',
                    123,
                    false,
                    true,
                    true,
                    false
                ),
                'expectedTypes' => ['Shipping', 'Default Billing'],
            ],
            [
                'address' => new SeleniumAddress(
                    'Wall',
                    'New York',
                    'United States',
                    'New York',
                    123,
                    false,
                    false,
                    true,
                    true
                ),
                'expectedTypes' => ['Default Billing', 'Default Shipping'],
                'deleteOnFinish' => false,
            ],
        ];
    }

    /**
     * @param AddressBookTestPage $page
     * @param $isGrid
     * @param string $gridXpath
     */
    protected function assertGridColumns($page, $isGrid, $gridXpath)
    {
        if (!$isGrid) {
            return;
        }

        $headers = $page->getGridHeaders($gridXpath);
        $this->assertEquals($headers, self::$gridHeaders);
    }

    /**
     * @param AbstractPage $page
     * @param bool $shouldHaveButtons
     * @param string $buttonType 'edit'|'delete'
     * @param string $userType   'user'|'customer'
     * @param bool $isGrid
     * @param int $nrOfButtons
     */
    protected function assertButtons($page, $shouldHaveButtons, $buttonType, $userType, $isGrid, $nrOfButtons)
    {
        $functionName = sprintf('get%sAddress%sButtons', ucfirst($userType), ucfirst($buttonType));
        $buttons = $page->{$functionName}($isGrid);
        if (!$shouldHaveButtons) {
            $this->assertEmpty($buttons);
        } else {
            $this->assertCount($nrOfButtons, $buttons);
            foreach ($buttons as $button) {
                $this->assertTrue($button->displayed());
            }
        }
    }

    /**
     * @param AdminCustomerAddressPages $addressPage
     * @param array $userInfo
     * @param int $nrOfUserAddresses
     * @param int $nrOfCustomerAddresses
     */
    protected function cleanupAddresses($addressPage, $userInfo, $nrOfUserAddresses, $nrOfCustomerAddresses)
    {
        if ($nrOfUserAddresses > 0 || $nrOfCustomerAddresses > 0) {
            $addressPage->deleteCustomerUserAddress($userInfo['userId'], $nrOfUserAddresses);
            $addressPage->deleteCustomerAddress($userInfo['customerId'], $nrOfCustomerAddresses);
        }
    }

    /**
     * @param AdminCustomerAddressPages $addressPage
     * @param array $userInfo
     * @param int $nrOfUserAddresses
     * @param int $nrOfCustomerAddresses
     */
    protected function createAddresses($addressPage, $userInfo, $nrOfUserAddresses, $nrOfCustomerAddresses)
    {
        if ($nrOfUserAddresses > 0 || $nrOfCustomerAddresses > 0) {
            $addressPage->addCustomerUserAddress($userInfo['userId'], $nrOfUserAddresses);
            $addressPage->addCustomerAddress($userInfo['customerId'], $nrOfCustomerAddresses);
        }
    }

    /**
     * @return AddressBookTestPage
     */
    protected function getAddressBookPage()
    {
        return new AddressBookTestPage($this);
    }

    protected function getAddAddressPage()
    {
        return new AddAddressPage($this);
    }

    /**
     * @return AdminCustomerAddressPages
     */
    protected function getCustomerAddressPage()
    {
        return new AdminCustomerAddressPages($this);
    }

    /**
     * @return CustomerAdminPages
     */
    protected function getCustomerAdminPage()
    {
        return new CustomerAdminPages($this);
    }

    /**
     * @param string|null $userAlias
     *
     * @return SeleniumCustomerUser[]|SeleniumCustomerUser
     */
    protected function getCustomerUsers($userAlias = null)
    {
        $users = [
            self::USER1 => new SeleniumCustomerUser(self::USER1, 'U1', 'U1', '123123', self::ROLE1, 'Company A'),
            self::USER2 => new SeleniumCustomerUser(self::USER2, 'U2', 'U2', '123123', self::ROLE2, 'Company A'),
            self::USER3 => new SeleniumCustomerUser(self::USER3, 'U3', 'U3', '123123', self::ROLE3, 'Company A'),
            self::USER4 => new SeleniumCustomerUser(self::USER4, 'U4', 'U4', '123123', self::ROLE4, 'Company A'),
        ];

        return $userAlias ? array_key_exists($userAlias, $users) ? $users[$userAlias] : null : $users;
    }

    /**
     * @return SeleniumCustomerUserTestRole[]
     */
    protected function getRoles()
    {
        return [
            new SeleniumCustomerUserTestRole(
                self::ROLE1,
                [
                    'Customer User' => ['View' => 'Customer'],
                    'Customer User Address' => [
                        'View' => 'Customer User',
                        'Create' => 'Customer User',
                        'Edit' => 'Customer User',
                        'Delete' => 'Customer User',
                    ],
                    'Address' => [
                        'View' => 'Customer',
                        'Create' => 'Customer',
                        'Edit' => 'Customer',
                        'Delete' => 'Customer',
                    ],
                ]
            ),
            new SeleniumCustomerUserTestRole(
                self::ROLE2,
                [
                    'Customer User' => ['View' => 'Customer'],
                    'Customer User Address' => [
                        'View' => 'Customer User',
                        'Edit' => 'Customer User',
                        'Delete' => 'Customer User',
                    ],
                    'Address' => ['View' => 'Customer', 'Edit' => 'Customer', 'Delete' => 'Customer'],
                ]
            ),
            new SeleniumCustomerUserTestRole(
                self::ROLE3,
                [
                    'Customer User' => ['View' => 'Customer'],
                    'Customer User Address' => ['View' => 'Customer User', 'Edit' => 'Customer User',],
                    'Address' => ['View' => 'Customer', 'Edit' => 'Customer',],
                ]
            ),
            new SeleniumCustomerUserTestRole(
                self::ROLE4,
                [
                    'Customer User' => ['View' => 'Customer'],
                    'Customer User Address' => ['View' => 'Customer User',],
                    'Address' => ['View' => 'Customer',],
                ]
            ),
        ];
    }

    /**
     * @return array
     */
    protected function getUsersInfo()
    {
        return AddressBookCache::$usersInfo;
    }
}
