<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * @method Roles openRoles(string $bundlePath)
 * @method Role add()
 * @method Role open(array $filter)
 * {@inheritdoc}
 */
class Roles extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Account User Role']";
    const URL = 'admin/account/user/role';

    /**
     * @return Role
     */
    public function entityNew()
    {
        return new Role($this->test);
    }

    /**
     * @return Role
     */
    public function entityView()
    {
        return new Role($this->test);
    }

    /**
     * {@inheritdoc}
     */
    public function action($entityData, $actionName = 'Update', $confirmation = false)
    {
        $entity = $this->getEntity($entityData);
        $flag = $entity->elements($this->test->using('xpath')->value(
            "td[contains(@class,'action-cell')]//a[contains(., '...')]"
        ));
        if (!empty($flag)) {
            $element = $entity->element($this->test->using('xpath')->value(
                "td[contains(@class,'action-cell')]//a[contains(., '...')]"
            ));
            $this->test->moveto($element);
            $this->test->byXpath("//ul[contains(@class,'dropdown-menu__action-cell')]" .
                "[contains(@class,'dropdown-menu__floating')]//a[@title='{$actionName}']")->click();
        } else {
            $entity->element(
                $this->test->using('xpath')
                    ->value("td[contains(@class,'action-cell')]//a[contains(., '{$actionName}')]")
            )->click();
        }

        if ($confirmation) {
            $this->test->byXPath("//div[div[contains(., 'Delete Account User Role')]]//a[contains(., 'Yes')]")->click();
        }

        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }
}
