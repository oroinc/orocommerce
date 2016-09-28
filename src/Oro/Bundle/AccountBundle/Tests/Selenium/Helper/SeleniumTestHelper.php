<?php

namespace Oro\Bundle\AccountBundle\Tests\Selenium\Helper;

trait SeleniumTestHelper
{
    /**
     * @param string $xpathQuery
     * @param bool $onlyVisible
     * @param bool $onlyOneElement
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element|\PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    public function getElement($xpathQuery, $onlyVisible = true, $onlyOneElement = true)
    {
        try {
            $matcher = $this->getTest()->using('xpath')->value($xpathQuery);
            $result = $this->getTest()->elements($matcher);
        } catch (\Throwable $e) {
            return $onlyOneElement ? null : [];
        }

        if (empty($result)) {
            return $onlyOneElement ? null : [];
        }

        if ($onlyVisible) {
            $result = $this->filterVisibleElements($result);
        } else {
            // put visible elements first
            $result = array_merge(
                $this->filterVisibleElements($result),
                $this->filterVisibleElements($result, false)
            );
        }

        if ($onlyOneElement) {
            $result = reset($result);
        }

        return $result;
    }

    /**
     * @param bool $andClose
     * @return $this
     */
    protected function save($andClose = false)
    {
        // click Save And Close dropdown
        $this->getElement("//div[contains(@class, 'title-buttons-container')]//a[contains(@class, 'dropdown-toggle')]")
            ->click();

        // click Save button
        $buttonQuery = $andClose
            ? "//ul//button[contains(text(), 'Save and Close')]"
            : "//ul//button[contains(text(), 'Save') and not(contains(text(), 'and'))]";
        $this->getElement($buttonQuery)->click();

        return $this;
    }

    /**
     * @return $this
     */
    protected function massDelete()
    {
        $deleteBtnSelector = "//ul[contains(@class, 'dropdown-menu__floating')]"
            . "//a[contains(@class, 'action')][ @title='Delete']";

        // Click mass actions button
        $this->getElement("//button[@title='Mass Actions']")->click();
        $this->getElement($deleteBtnSelector)->click();

        $this->waitForAjax();
        $this->getElement("//a[text() = 'Yes, Delete']")->click();

        return $this;
    }

    /**
     * @param \PHPUnit_Extensions_Selenium2TestCase_Element[] $elements
     * @param bool $visible
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    protected function filterVisibleElements($elements, $visible = true)
    {
        return array_filter($elements, function ($element) use ($visible) {
            return $visible ? $element->displayed() : !$element->displayed();
        });
    }
}
