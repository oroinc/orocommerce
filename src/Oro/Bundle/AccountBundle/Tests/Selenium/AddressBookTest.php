<?php

namespace Oro\Bundle\AccountBundle\Tests\Selenium;

use Oro\Bundle\AccountBundle\Tests\Selenium\Cache\AddressBookCache;
use Oro\Bundle\AccountBundle\Tests\Selenium\Entity\SeleniumAccountUser;
use Oro\Bundle\AccountBundle\Tests\Selenium\Entity\SeleniumAccountUserTestRole;
use Oro\Bundle\AccountBundle\Tests\Selenium\Entity\SeleniumAddress;
use Oro\Bundle\AccountBundle\Tests\Selenium\Pages\AdminAccountAddressPages;
use Oro\Bundle\AccountBundle\Tests\Selenium\Pages\AccountAdminPages;
use Oro\Bundle\AccountBundle\Tests\Selenium\Pages\AddAddressPage;
use Oro\Bundle\AccountBundle\Tests\Selenium\Pages\AddressBookTestPage;
use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

/**
 * Class AddressBookTest
 *
 * @package Oro\Bundle\AccountBundle\Tests\Selenium
 */
class AddressBookTest extends Selenium2TestCase
{
    // View/create/edit/delete account address and account user address
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

        $accountPage = $this->getAccountAdminPage();
        $accountPage->createAccountUserRoles($this->getRoles());

        AddressBookCache::$usersInfo = $accountPage->createAccountUsersWithRoles($this->getAccountUsers());

        // add one default address for each user
        $accountAddressPage = $this->getAccountAddressPage();
        foreach (AddressBookCache::$usersInfo as $userInfo) {
            $accountAddressPage->addAccountUserAddress($userInfo['userId'], 1);
        }
    }

    /**
     * @param SeleniumAccountUser $frontendUser
     * @param bool $usrAddrIsGrid           Account user address should be in grid on not (list)
     * @param bool $accAddrIsGrid           Account address should be in grid on not (list)
     * @param bool $usrAddrHasAddBtn        Account user address should have add address button
     * @param bool $accAddrHasAddBtn        Account address should have add address button
     * @param bool $usrAddrHasEditButtons   Account user address should have edit address button
     * @param bool $accAddrHasEditBtn       Account address should have edit address button
     * @param bool $usrAddrHasDeleteButtons Account user address should have delete address button
     * @param bool $accAddrHasDeleteBtn     Account address should have delete address button
     * @param int $nrOfUsrAddr              Number of expected account users addresses
     * @param int $nrOfAccAddr              Number of expected account addresses
     * @param int $nrOfUsrAddrToCreate      Number of account user addresses that should be created
     * @param int $nrOfAccAddrToCreate      Number of account addresses that should be created
     * @param int $nrOfUsrAddrToDelete      Number of account user addresses that should be deleted
     * @param int $nrOfAccAddrToDelete      Number of account addresses that should be deleted
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
        SeleniumAccountUser $frontendUser,
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
        $addressPage = $this->getAccountAddressPage();
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

            // Account User Address section assertions
            $this->assertGridColumns($page, $usrAddrIsGrid, AddressBookTestPage::USER_ADDRESS_BLOCK_SELECTOR);

            $this->assertContains($usrAddrGridClass, $page->getUserAddressBlock()->attribute('class'));
            $this->assertButtons($page, $usrAddrIsGrid, 'showOnMap', 'user', $usrAddrIsGrid, $nrOfUsrAddr);
            $this->assertButtons($page, $usrAddrHasAddBtn, 'add', 'user', $usrAddrIsGrid, $nrOfUsrAddrAddBtn);
            $this->assertButtons($page, $usrAddrHasEditButtons, 'edit', 'user', $usrAddrIsGrid, $nrOfUsrAddr);
            $this->assertButtons($page, $usrAddrHasDeleteButtons, 'delete', 'user', $usrAddrIsGrid, $nrOfUsrAddr);

            // Account Address section assertions
            $this->assertGridColumns($page, $accAddrIsGrid, AddressBookTestPage::ACCOUNT_ADDRESS_BLOCK_SELECTOR);
            $this->assertContains($accAddrGridClass, $page->getAccountAddressBlock()->attribute('class'));
            $this->assertButtons($page, $usrAddrIsGrid, 'showOnMap', 'account', $accAddrIsGrid, $nrOfAccAddr);
            $this->assertButtons($page, $accAddrHasAddBtn, 'add', 'account', $accAddrIsGrid, $nrOfAccAddrAddBtn);
            $this->assertButtons($page, $accAddrHasEditBtn, 'edit', 'account', $accAddrIsGrid, $nrOfAccAddr);
            $this->assertButtons($page, $accAddrHasDeleteBtn, 'delete', 'account', $accAddrIsGrid, $nrOfAccAddr);
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
            [$this->getAccountUsers(self::USER1), false, false, true, true, true, true, true, true, 1, 1],
            [$this->getAccountUsers(self::USER2), false, false, false, false, true, true, true, true, 1, 1],
            [$this->getAccountUsers(self::USER3), false, false, false, false, true, true, false, false, 1, 1],
            [$this->getAccountUsers(self::USER4), false, false, false, false, false, false, false, false, 1, 1],
            [$this->getAccountUsers(self::USER1), true, true, true, true, true, true, true, true, 9, 9, 8, 8, 8, 0],
            [$this->getAccountUsers(self::USER2), true, true, false, false, true, true, true, true, 9, 9, 8, 0, 8, 0],
            [$this->getAccountUsers(self::USER3), true, true, false, false, true, true, false, false, 9, 9, 8, 0, 8, 0],
            [
                $this->getAccountUsers(self::USER4),
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
        $user = $this->getAccountUsers(self::USER1);
        $page->login($user->email, $user->password);

        $page->getTest()->url(AddressBookTestPage::ADDRESS_BOOK_URL);
        $page->waitPageToLoad();
        $page->deleteAccountAddresses(false);
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
    public function testAddAccountAddress()
    {
        $this->markTestSkipped("Skipped until task BB-4263 gets resolved!");
        $user = $this->getAccountUsers(self::USER1);
        $addressPage = $this->getAddressBookPage();
        $addressPage->login($user->email, $user->password);

        $addAddressPage = $this->getAddAddressPage();
        $userInfo = $this->getUsersInfo()[$user->email];

        foreach ($this->getAddressesWithTypes() as $addressInfo) {
            $addAddressPage->openAddAccountAddressPage($userInfo['accountId']);
            $addAddressPage->addNewAddress($addressInfo['address']);

            $types = $addressPage->getElement(
                AddressBookTestPage::ACCOUNT_ADDRESS_BLOCK_SELECTOR
                . "//li[1]/div[contains(@class, 'address-list__type')]"
            )->text();
            $types = explode("\n", $types);
            $this->assertEquals(sort($addressInfo['expectedTypes']), sort($types));

            if (!isset($addressInfo['deleteOnFinish']) || $addressInfo['deleteOnFinish']) {
                $addressPage->deleteAccountAddresses(false);
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
        $this->getAccountAdminPage()
            ->deleteRoles($this->getRoles())
            ->deleteAccountUsers($this->getAccountUsers());
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
     * @param string $userType   'user'|'account'
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
     * @param AdminAccountAddressPages $addressPage
     * @param array $userInfo
     * @param int $nrOfUserAddresses
     * @param int $nrOfAccountAddresses
     */
    protected function cleanupAddresses($addressPage, $userInfo, $nrOfUserAddresses, $nrOfAccountAddresses)
    {
        if ($nrOfUserAddresses > 0 || $nrOfAccountAddresses > 0) {
            $addressPage->deleteAccountUserAddress($userInfo['userId'], $nrOfUserAddresses);
            $addressPage->deleteAccountAddress($userInfo['accountId'], $nrOfAccountAddresses);
        }
    }

    /**
     * @param AdminAccountAddressPages $addressPage
     * @param array $userInfo
     * @param int $nrOfUserAddresses
     * @param int $nrOfAccountAddresses
     */
    protected function createAddresses($addressPage, $userInfo, $nrOfUserAddresses, $nrOfAccountAddresses)
    {
        if ($nrOfUserAddresses > 0 || $nrOfAccountAddresses > 0) {
            $addressPage->addAccountUserAddress($userInfo['userId'], $nrOfUserAddresses);
            $addressPage->addAccountAddress($userInfo['accountId'], $nrOfAccountAddresses);
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
     * @return AdminAccountAddressPages
     */
    protected function getAccountAddressPage()
    {
        return new AdminAccountAddressPages($this);
    }

    /**
     * @return AccountAdminPages
     */
    protected function getAccountAdminPage()
    {
        return new AccountAdminPages($this);
    }

    /**
     * @param string|null $userAlias
     *
     * @return SeleniumAccountUser[]|SeleniumAccountUser
     */
    protected function getAccountUsers($userAlias = null)
    {
        $users = [
            self::USER1 => new SeleniumAccountUser(self::USER1, 'U1', 'U1', '123123', self::ROLE1, 'Company A'),
            self::USER2 => new SeleniumAccountUser(self::USER2, 'U2', 'U2', '123123', self::ROLE2, 'Company A'),
            self::USER3 => new SeleniumAccountUser(self::USER3, 'U3', 'U3', '123123', self::ROLE3, 'Company A'),
            self::USER4 => new SeleniumAccountUser(self::USER4, 'U4', 'U4', '123123', self::ROLE4, 'Company A'),
        ];

        return $userAlias ? array_key_exists($userAlias, $users) ? $users[$userAlias] : null : $users;
    }

    /**
     * @return SeleniumAccountUserTestRole[]
     */
    protected function getRoles()
    {
        return [
            new SeleniumAccountUserTestRole(
                self::ROLE1,
                [
                    'Account User' => ['View' => 'Account'],
                    'Account User Address' => [
                        'View' => 'Account User',
                        'Create' => 'Account User',
                        'Edit' => 'Account User',
                        'Delete' => 'Account User',
                    ],
                    'Address' => [
                        'View' => 'Account',
                        'Create' => 'Account',
                        'Edit' => 'Account',
                        'Delete' => 'Account',
                    ],
                ]
            ),
            new SeleniumAccountUserTestRole(
                self::ROLE2,
                [
                    'Account User' => ['View' => 'Account'],
                    'Account User Address' => [
                        'View' => 'Account User',
                        'Edit' => 'Account User',
                        'Delete' => 'Account User',
                    ],
                    'Address' => ['View' => 'Account', 'Edit' => 'Account', 'Delete' => 'Account'],
                ]
            ),
            new SeleniumAccountUserTestRole(
                self::ROLE3,
                [
                    'Account User' => ['View' => 'Account'],
                    'Account User Address' => ['View' => 'Account User', 'Edit' => 'Account User',],
                    'Address' => ['View' => 'Account', 'Edit' => 'Account',],
                ]
            ),
            new SeleniumAccountUserTestRole(
                self::ROLE4,
                [
                    'Account User' => ['View' => 'Account'],
                    'Account User Address' => ['View' => 'Account User',],
                    'Address' => ['View' => 'Account',],
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
