<?php

namespace Oro\Bundle\AccountBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

use Oro\Bundle\AccountBundle\Tests\Selenium\Entity\SeleniumAccountUser;
use Oro\Bundle\AccountBundle\Tests\Selenium\Entity\SeleniumAccountUserTestRole;
use Oro\Bundle\AccountBundle\Tests\Selenium\Helper\SeleniumTestHelper;

class AccountAdminPages extends AbstractPage
{
    use SeleniumTestHelper;

    /**
     * @param SeleniumAccountUserTestRole[] $roles
     * @return $this
     */
    public function createAccountUserRoles($roles)
    {
        foreach ($roles as $role) {
            $this->test->url('/admin/account/user/role/create');
            $this->waitPageToLoad();
            $this->waitForAjax();
            $this->test->byName('oro_account_account_user_role[label]')->value($role->roleName);
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
     * @param SeleniumAccountUser[] $users
     * @return array
     */
    public function createAccountUsersWithRoles($users)
    {
        $accountInputSelector = "//div[starts-with(@id, 'oro_account_account_user_account-uid')]"
            . "//div[contains(@class, 'input-widget')]";
        $roleSelector = "//label[contains(text(),'%s')]/preceding-sibling::input";

        $usersInfo = [];
        foreach ($users as $user) {
            $this->test->url('/admin/account/user/create');
            $this->waitPageToLoad();
            $this->waitForAjax();
            $this->test->byName('oro_account_account_user[firstName]')->value($user->firstName);
            $this->test->byName('oro_account_account_user[lastName]')->value($user->lastName);
            $this->test->byName('oro_account_account_user[email]')->value($user->email);
            $this->test->byName('oro_account_account_user[plainPassword][first]')->value($user->password);
            $this->test->byName('oro_account_account_user[plainPassword][second]')->value($user->password);

            // select account
            $this->getElement($accountInputSelector)->click();
            $this->waitForAjax();
            $this->getElement(
                sprintf("//div[@class='select2-result-label'][text()='%s']", $user->accountName)
            )->click();
            $this->waitForAjax();
            $this->getElement(sprintf($roleSelector, $user->role))->click();
            $this->waitForAjax();
            $this->save(true);
            $this->waitPageToLoad();
            $this->waitForAjax();

            $accountUrl = $this->getElement(sprintf("//a[text() ='%s']", $user->accountName))->attribute('href');
            $usersInfo[$user->email] = [
                'userId' => abs(filter_var($this->test->url(), FILTER_SANITIZE_NUMBER_INT)),
                'accountId' => abs(filter_var($accountUrl, FILTER_SANITIZE_NUMBER_INT))
            ];
        }

        return $usersInfo;
    }

    /**
     * @param SeleniumAccountUserTestRole[] $roles
     * @return $this
     */
    public function deleteRoles($roles)
    {
        $this->test->url('/admin/account/user/role');
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
     * @param SeleniumAccountUser[] $users
     * @return $this
     */
    public function deleteAccountUsers($users)
    {
        $this->test->url('/admin/account/user/');
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
        $xpath = "//div[starts-with(@id,'grid-account-user-role-permission-grid')]//div[text()='%s']"
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
