<?php

namespace Oro\Bundle\CustomerBundle\Tests\Selenium\Pages;

use Oro\Bundle\CustomerBundle\Tests\Selenium\Entity\SeleniumCustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Selenium\Entity\SeleniumCustomerUserTestRole;
use Oro\Bundle\CustomerBundle\Tests\Selenium\Helper\SeleniumTestHelper;
use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

class CustomerAdminPages extends AbstractPage
{
    use SeleniumTestHelper;

    /**
     * @param SeleniumCustomerUserTestRole[] $roles
     * @return $this
     */
    public function createCustomerUserRoles($roles)
    {
        foreach ($roles as $role) {
            $this->test->url('/admin/customer/user/role/create');
            $this->waitPageToLoad();
            $this->waitForAjax();
            $this->test->byName('oro_customer_customer_user_role[label]')->value($role->roleName);
            foreach ($role->permissions as $permissionEntity => $permissionActions) {
                foreach ($permissionActions as $actionName => $actionTarget) {
                    $this->getPermissionElement($permissionEntity, $actionName)->click();
                    $this->waitForAjax();
                    $this->getPermissionDropdownElement($actionTarget)->click();
                    $this->waitForAjax();
                }
            }
            $this->save();
            $this->waitPageToLoad();
            $this->waitForAjax();
        }

        return $this;
    }

    /**
     * @param SeleniumCustomerUser[] $users
     * @return array
     */
    public function createCustomerUsersWithRoles($users)
    {
        $customerInputSelector = "//div[starts-with(@id, 'oro_customer_customer_user_customer-uid')]"
            . "//div[contains(@class, 'input-widget')]";
        $roleSelector = "//label[contains(text(),'%s')]/preceding-sibling::input";

        $usersInfo = [];
        foreach ($users as $user) {
            $this->test->url('/admin/customer/user/create');
            $this->waitPageToLoad();
            $this->waitForAjax();
            $this->test->byName('oro_customer_customer_user[firstName]')->value($user->firstName);
            $this->test->byName('oro_customer_customer_user[lastName]')->value($user->lastName);
            $this->test->byName('oro_customer_customer_user[email]')->value($user->email);
            $this->test->byName('oro_customer_customer_user[plainPassword][first]')->value($user->password);
            $this->test->byName('oro_customer_customer_user[plainPassword][second]')->value($user->password);

            // select customer
            $this->getElement($customerInputSelector)->click();
            $this->waitForAjax();
            $this->getElement(sprintf("//div[@class='select2-result-label'][text()='%s']", $user->customerName))
                ->click();
            $this->waitForAjax();
            $this->getElement(sprintf($roleSelector, $user->role))->click();
            $this->waitForAjax();
            $this->save(true);
            $this->waitPageToLoad();
            $this->waitForAjax();

            $customerUrl = $this->getElement(sprintf("//a[text() ='%s']", $user->customerName))->attribute('href');
            $usersInfo[$user->email] = [
                'userId' => abs(filter_var($this->test->url(), FILTER_SANITIZE_NUMBER_INT)),
                'customerId' => abs(filter_var($customerUrl, FILTER_SANITIZE_NUMBER_INT))
            ];
        }

        return $usersInfo;
    }

    /**
     * @param SeleniumCustomerUserTestRole[] $roles
     * @return $this
     */
    public function deleteRoles($roles)
    {
        $this->test->url('/admin/customer/user/role');
        $this->waitPageToLoad();
        $this->waitForAjax();

        $roleInputSelector = "//td[text() = '%s']/preceding-sibling::td/input";

        foreach ($roles as $role) {
            // select each role's checkbox
            $this->getElement(sprintf($roleInputSelector, $role->roleName))->click();
        }

        // Click mass actions button
        $this->massDelete();

        return $this;
    }

    /**
     * @param SeleniumCustomerUser[] $users
     * @return $this
     */
    public function deleteCustomerUsers($users)
    {
        $this->test->url('/admin/customer/user/');
        $this->waitPageToLoad();
        $this->waitForAjax();

        $userCheckbox = "//td[text()='%s']/parent::tr//input[@type='checkbox']";

        foreach ($users as $user) {
            $this->getElement(sprintf($userCheckbox, $user->email))->click();
        }

        $this->massDelete();

        return $this;
    }

    /**
     * @param string $entityLabel
     * @param string $permissionName
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element
     */
    protected function getPermissionElement($entityLabel, $permissionName)
    {
        $xpath = "//div[starts-with(@id,'grid-customer-user-role-permission-grid')]//div[text()='%s']"
            . "/following-sibling::ul[@data-name='action-permissions-items']//span[text()='%s']";
        $selector = sprintf($xpath, $entityLabel, $permissionName);

        return $this->getElement($selector);
    }

    /**
     * @param string $selectOptionValue
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element
     */
    protected function getPermissionDropdownElement($selectOptionValue)
    {
        $selector = sprintf(
            "//ul[@class='dropdown-menu-collection__list']//a[contains(text(), '%s')]",
            $selectOptionValue
        );

        return $this->getElement($selector);
    }
}
