<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

use OroB2B\Bundle\AccountBundle\Tests\Selenium\Helper\SeleniumTestHelper;

class AddressBookTestPage extends AbstractPage
{
    use SeleniumTestHelper;

    const ACCOUNT_ADDRESS_BLOCK_SELECTOR =
        "//h1[contains(@class,'page-title')]/following-sibling::div[2]";
    const USER_ADDRESS_BLOCK_SELECTOR =
        "//h1[contains(@class,'page-title')]/following-sibling::div[4]";

    /**
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function login($username = 'AmandaRCole@example.org', $password = 'AmandaRCole@example.org')
    {
        $this->test->url('/account/user/login');
        $this->waitPageToLoad();
        $this->waitForAjax();

        $this->test->byId('userNameSignIn')->clear();
        $this->test->byId('userNameSignIn')->value($username);

        $this->test->byId('passwordSignIn')->clear();
        $this->test->byId('passwordSignIn')->value($password);

        $this->test->byXPath("//input[@type='submit']")->click();

        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function loginAdmin(
        $username = PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN,
        $password = PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS
    ) {
        $this->test->url('/admin/user/login');
        $this->waitPageToLoad();
        $this->test->byId('prependedInput')->clear();
        $this->test->byId('prependedInput')->value($username);
        $this->test->byId('prependedInput2')->clear();
        $this->test->byId('prependedInput2')->value($password);

        $this->test->byXPath("//button[@type='submit']")->click();

        return $this;
    }

    /**
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function getAccountAddressBlock()
    {
        return $this->getElement(self::ACCOUNT_ADDRESS_BLOCK_SELECTOR);
    }

    /**
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function getUserAddressBlock()
    {
        return $this->getElement(self::USER_ADDRESS_BLOCK_SELECTOR);
    }

    /**
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element|\PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    public function getAccountAddressAddButtons()
    {
        return $this->getElement("//a[text()='Add Company Address']", true, false);
    }

    /**
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element|\PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    public function getUserAddressAddButtons()
    {
        return $this->getElement("//a[text()='Add Address']", true, false);
    }

    /**
     * @param bool $grid
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element|\PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    public function getAccountAddressEditButtons($grid = true)
    {
        return $this->getElement(
            $this->getEditButtonSelector(self::ACCOUNT_ADDRESS_BLOCK_SELECTOR, $grid),
            true,
            false
        );
    }

    /**
     * @param bool $grid
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element|\PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    public function getUserAddressEditButtons($grid = true)
    {
        return $this->getElement(
            $this->getEditButtonSelector(self::USER_ADDRESS_BLOCK_SELECTOR, $grid),
            true,
            false
        );
    }

    /**
     * @param bool $grid
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element|\PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    public function getAccountAddressDeleteButtons($grid = true)
    {
        return $this->getElement(
            $this->getDeleteButtonSelector(self::ACCOUNT_ADDRESS_BLOCK_SELECTOR, $grid),
            true,
            false
        );
    }

    /**
     * @param bool $grid
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element|\PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    public function getUserAddressDeleteButtons($grid = true)
    {
        return $this->getElement(
            $this->getDeleteButtonSelector(self::USER_ADDRESS_BLOCK_SELECTOR, $grid),
            true,
            false
        );
    }

    /**
     * @param string $selectorPrefix
     * @param bool $grid
     * @return string
     */
    protected function getEditButtonSelector($selectorPrefix, $grid = false)
    {
        $iconClass = $grid ? "icon-pencil" : 'cf-pencil';

        return $selectorPrefix . sprintf("//i[contains(@class, '%s')]", $iconClass);
    }

    /**
     * @param string $selectorPrefix
     * @param bool $grid
     * @return string
     */
    protected function getDeleteButtonSelector($selectorPrefix, $grid = false)
    {
        $iconClass = $grid ? "icon-trash" : 'cf-trash';

        return $selectorPrefix . sprintf("//i[contains(@class, '%s')]", $iconClass);
    }
}
