<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When /^(?:|I )fill in Landing Page Titles field with "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function fillInLandingPageTitlesFieldWith($value)
    {
        $productNameField = $this->createElement('LandingPageTitlesField');
        $productNameField->focus();
        $productNameField->setValue($value);
        $productNameField->blur();
        $this->waitForAjax();
    }

    /**
     * Example: When I fill in WYSIWYG "CMS Page Content" with "Content"
     *
     * @When /^(?:|I )fill in WYSIWYG "(?P<wysiwygElementName>[^"]+)" with "(?P<text>(?:[^"]|\\")*)"$/
     * @param string $wysiwygElementName
     * @param string $text
     */
    public function fillWysiwygContentField($wysiwygElementName, $text)
    {
        $wysiwygContentElement = $this->createElement($wysiwygElementName);
        self::assertTrue($wysiwygContentElement->isIsset(), sprintf(
            'WYSIWYG element "%s" not found on page',
            $wysiwygElementName
        ));

        $function = <<<JS
(function(){
    $("#{$wysiwygContentElement->getAttribute('id')}").val("{$text}");
})()
JS;
        $this->getSession()->executeScript($function);
    }
}
