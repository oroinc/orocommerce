<?php

namespace Oro\Bundle\CustomerBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

class Role extends AbstractPageEntity
{
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $accessLevel;

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->test->byXpath("//*[@data-ftid='oro_account_account_user_role_label']")->value($label);
        return $this;
    }

    /**
     * @return null|string
     */
    public function getLabel()
    {
        return $this->test->byXpath("//*[@data-ftid='oro_account_account_user_role_label']")->value();
    }

    /**
     * @param string $entityName string of ACL resource name
     * @param array $aclAction array of actions such as create, edit, delete, view, assign
     * @param string $accessLevel
     *
     * @return $this
     */
    public function setEntity($entityName, array $aclAction, $accessLevel)
    {
        foreach ($aclAction as $action) {
            $action = strtoupper($action);
            $xpath = $this->test->byXpath(
                "//div[strong/text() = '{$entityName}']/ancestor::tr//input" .
                "[contains(@name, '[$action][accessLevel')]/preceding-sibling::a"
            );
            $this->test->moveto($xpath);
            $xpath->click();
            $this->waitPageToLoad();
            $this->waitForAjax();
            $this->accessLevel = $this->test->select(
                $this->test->byXpath(
                    "//div[strong/text() = '{$entityName}']/ancestor::tr//select" .
                    "[contains(@name, '[$action][accessLevel')]"
                )
            );
            if ($accessLevel === 'System'
                && !$this->isElementPresent(
                    "//div[strong/text() = '{$entityName}']/ancestor::tr//select[contains(@name, '[$action]".
                    "[accessLevel')]/option[text()='{$accessLevel}']"
                )) {
                $accessLevel = 'Organization';
            }
            $this->accessLevel->selectOptionByLabel($accessLevel);
        }

        return $this;
    }

    /**
     * @param array $capabilityName array of Capability ACL resources
     * @param string $accessLevel
     *
     * @return $this
     */
    public function setCapability(array $capabilityName, $accessLevel)
    {
        foreach ($capabilityName as $name) {
            $xpath = $this->test->byXpath("//div[strong/text() = '{$name}']/following-sibling::div//a");
            $this->test->moveto($xpath);
            $xpath->click();
            $this->waitForAjax();
            $this->accessLevel = $this->test->select(
                $this->test->byXpath("//div[strong/text() = '{$name}']/following-sibling::div//select")
            );
            $this->accessLevel->selectOptionByLabel($accessLevel);
        }

        return $this;
    }
}
