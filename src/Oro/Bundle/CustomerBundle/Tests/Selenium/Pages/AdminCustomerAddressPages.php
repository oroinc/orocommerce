<?php

namespace Oro\Bundle\CustomerBundle\Tests\Selenium\Pages;

use Oro\Bundle\CustomerBundle\Tests\Selenium\Helper\SeleniumTestHelper;
use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

class AdminCustomerAddressPages extends AbstractPage
{
    use SeleniumTestHelper;

    const ACCOUNT_USER_ADDRESS_URL = "/admin/customer/user/view/%s";
    const ACCOUNT_ADDRESS_URL = "admin/customer/view/%s";

    const ACCOUNT_USER_ADDRESS_INPUT_PREFIX = "oro_customer_customer_user_typed_address";
    const ACCOUNT_ADDRESS_INPUT_PREFIX = "oro_customer_typed_address";

    /**
     * @param int $customerId
     * @param int $nrOfAddresses
     */
    public function addCustomerAddress($customerId, $nrOfAddresses = 1)
    {
        $this->addAddressInAdmin(
            sprintf(self::ACCOUNT_ADDRESS_URL, $customerId),
            self::ACCOUNT_ADDRESS_INPUT_PREFIX,
            $nrOfAddresses
        );
    }

    /**
     * @param int $userId
     * @param int $nrOfAddresses
     */
    public function addCustomerUserAddress($userId, $nrOfAddresses = 1)
    {
        $this->addAddressInAdmin(
            sprintf(self::ACCOUNT_USER_ADDRESS_URL, $userId),
            self::ACCOUNT_USER_ADDRESS_INPUT_PREFIX,
            $nrOfAddresses
        );
    }

    /**
     * @param int $customerId
     * @param int $nrOfAddresses
     */
    public function deleteCustomerAddress($customerId, $nrOfAddresses = 1)
    {
        $this->deleteAddressInAdmin(sprintf(self::ACCOUNT_ADDRESS_URL, $customerId), $nrOfAddresses);
    }

    /**
     * @param int $userId
     * @param int $nrOfAddresses
     */
    public function deleteCustomerUserAddress($userId, $nrOfAddresses = 1)
    {
        $this->deleteAddressInAdmin(sprintf(self::ACCOUNT_USER_ADDRESS_URL, $userId), $nrOfAddresses);
    }

    /**
     * @param string $pageUrl
     * @param string $inputPrefix
     * @param int $nrOfAddresses
     * @return $this
     */
    protected function addAddressInAdmin($pageUrl, $inputPrefix, $nrOfAddresses = 1)
    {
        if ($nrOfAddresses < 1) {
            return $this;
        }

        $this->test->url($pageUrl);
        $this->waitPageToLoad();
        $this->waitForAjax();

        for ($i = 1; $i <= $nrOfAddresses; $i++) {
            $this->getElement("//button[text()=' + Add Address']")->click();
            $this->waitForAjax();

            // select country
            $this->getElement("//div[contains(@id, 's2id_" . $inputPrefix . "_country-uid')]")
                ->click();
            $this->waitForAjax();
            $this->test->keys(\PHPUnit_Extensions_Selenium2TestCase_Keys::ENTER);
            $this->waitForAjax();

            $this->test->byName($inputPrefix . "[label]")->value('test');
            $this->test->byName($inputPrefix . "[street]")->value('1');
            $this->test->byName($inputPrefix . "[city]")->value('Kabul');

            // select first state
            $this->getElement("//div[contains(@id, 's2id_" . $inputPrefix . "_region-uid')]")->click();
            $this->waitForAjax();
            $this->test->keys(\PHPUnit_Extensions_Selenium2TestCase_Keys::ENTER);

            $this->test->byName($inputPrefix . "[postalCode]")->value('123123');

            // Save
            $this->getElement("//button[text()='Save']")->click();
            $this->waitForAjax();
        }

        return $this;
    }

    /**
     * @param $pageUrl
     * @param int $nrOfAddresses
     * @return $this
     */
    protected function deleteAddressInAdmin($pageUrl, $nrOfAddresses = 1)
    {
        if ($nrOfAddresses < 1) {
            return $this;
        }

        $this->test->url($pageUrl);
        $this->waitPageToLoad();
        $this->waitForAjax();

        $removeButtonSelector = "//div[contains(@class, 'map-item')]//button[@title='Remove']";
        $itemsDeleted = 0;
        $deleteButtons = $this->getElement($removeButtonSelector, false, false);

        // click currently active element to make the scrollbar appear (otherwise first delete click won't work)
        $this->getElement("//div[contains(@class, 'map-address-list')]//div[contains(@class, 'active')]")->click();
        foreach ($deleteButtons as $deleteButton) {
            if ($itemsDeleted >= $nrOfAddresses) {
                break;
            }
            $deleteButton->click();
            $this->waitForAjax();
            $itemsDeleted++;
        }

        return $this;
    }
}
