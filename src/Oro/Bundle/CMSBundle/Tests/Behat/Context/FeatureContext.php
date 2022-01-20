<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\CMSBundle\Tests\Behat\Element\WysiwygCodeTypeBlockEditor;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When /^(?:|I )type "(?P<value>(?:[^"]|\\")*)" in Landing Page Titles field$/
     */
    public function typeInLandingPageTitlesField(string $value): void
    {
        $productNameField = $this->createElement('LandingPageTitlesField');
        $this->getDriver()->typeIntoInput($productNameField->getXpath(), $value);
    }

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
     * Example: I open code editor of code type block containing the text "Same text 1"
     *
     * @When /^(?:|I )open code editor of code type block containing the text "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function openCodeEditorOfCodeTypeBlockContainingTheText(string $value): void
    {
        $codeTypeBlockElement = $this->findCodeTypeBlock($value);
        $this->openCodeTypeBlockEditor($codeTypeBlockElement);
    }

    /**
     * Example: I fill the code type block containing the text "Same text 1" with the value "Same text 2"
     *
     * @codingStandardsIgnoreStart
     *
     * @When /^(?:|I )fill the code type block containing the text "(?P<existingValue>(?:[^"]|\\")*)" with the value "(?P<newValue>(?:[^"]|\\")*)"$/
     *
     * @codingStandardsIgnoreEnd
     */
    public function fillTheCodeTypeBlockContainingTheTextWithTheValue(string $existingValue, string $newValue): void
    {
        $codeTypeBlockElement = $this->findCodeTypeBlock($existingValue);
        $this->openCodeTypeBlockEditor($codeTypeBlockElement);

        /** @var WysiwygCodeTypeBlockEditor $editor */
        $editor = $this->createElement('WysiwygCodeTypeBlockEditor');
        self::assertNotNull($editor, 'Wysiwyg `code` type block editor not found!');

        $editor->setValue($newValue);
        $editor->findButton('Save')->click();
    }

    private function findCodeTypeBlock(string $containingValue): Element
    {
        // Switch to WYSIWYG editor iframe.
        $this->getDriver()->switchToIFrame(0);

        /**
         * Wait for WYSIWYG editor is initialized.
         *
         * @var Element $element
         */
        $element = $this->spin(function () use ($containingValue) {
            $element = $this->findElementContains('WysiwygCodeTypeBlock', $containingValue);

            return $element->isIsset() ? $element : false;
        }, 3);
        self::assertNotNull($element, sprintf('Wysiwyg `code` type block with text "%s" not found!', $containingValue));

        // Since the modal window is displayed outside the iframe, switch to the main DOM.
        $this->getDriver()->switchToIFrame(null);

        return $element;
    }

    private function openCodeTypeBlockEditor(Element $codeTypeBlockElement): void
    {
        // Switch to WYSIWYG editor iframe.
        $this->getDriver()->switchToIFrame(0);

        // Since we use the selected block to display the content in the Mirror editor, it is necessary to
        // simulate the entire cycle of opening a modal window, namely its selection(one click)
        // and open editor ('one click' and 'double click' events).
        $codeTypeBlockElement->click();
        $codeTypeBlockElement->doubleClick();

        // Since the modal window is displayed outside the iframe, switch to the main DOM.
        $this->getDriver()->switchToIFrame(null);
    }
}
