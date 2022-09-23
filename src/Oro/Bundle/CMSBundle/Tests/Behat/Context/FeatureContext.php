<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\CMSBundle\Tests\Behat\Element\WysiwygCodeTypeBlockEditor;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

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
     * Example: When I import content "Content" to "CMS Page Content" WYSIWYG editor
     *
     * @When /^(?:|I )import content "(?P<text>(?:[^"]|\\")*)" to "(?P<wysiwygElementName>[^"]+)" WYSIWYG editor$/
     * @param string $text
     * @param string $wysiwygElementName
     */
    public function importContentToWysiwygEditor($text, $wysiwygElementName)
    {
        $wysiwygContentElement = $this->createElement($wysiwygElementName);
        self::assertTrue($wysiwygContentElement->isIsset(), sprintf(
            'WYSIWYG element "%s" not found on page',
            $wysiwygElementName
        ));

        $importDialog = $this->createElement('Import Button');
        $importDialog->click();

        /** @var WysiwygCodeTypeBlockEditor $editor */
        $editor = $this->createElement('WysiwygCodeTypeBlockEditor');
        self::assertNotNull($editor, 'Wysiwyg `code` type block editor not found!');

        $editor->setValue(stripslashes($text));
        $this->waitForAjax();
        $editor->findButton('Import')->click();
    }

    /**
     * @codingStandardsIgnoreStart
     *
     * Example: When I should see imported "Content" content in "CMS Page Content" WYSIWYG editor
     *
     * @When /^(?:|I )should see imported "(?P<text>(?:[^"]|\\")*)" content in "(?P<wysiwygElementName>[^"]+)" WYSIWYG editor$/
     * @param string $text
     * @param string $wysiwygElementName
     * @param string $importDialogName
     *
     * @codingStandardsIgnoreEnd
     */
    public function shouldSeeImportedContent($text, $wysiwygElementName, $importDialogName = "Import Button")
    {
        $wysiwygContentElement = $this->createElement($wysiwygElementName);
        self::assertTrue($wysiwygContentElement->isIsset(), sprintf(
            'WYSIWYG element "%s" not found on page',
            $wysiwygElementName
        ));

        $importDialog = $this->createElement($importDialogName);
        $importDialog->click();

        /** @var WysiwygCodeTypeBlockEditor $editor */
        $editor = $this->createElement('WysiwygCodeTypeBlockEditor');
        self::assertNotNull($editor, 'Wysiwyg `code` type block editor not found!');

        $importedContent = str_replace("\n", "", $editor->getValue());
        self::assertEquals(stripslashes($text), $importedContent);

        $editor->findButton('Import')->click();
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

    /**
     * @Then /^(?:|I )should see following content templates categories in GrapesJs:$/
     */
    public function shouldSeeTheFollowingContentTemplatesCategories(TableNode $table): void
    {
        $categories = $this->findAllElements('GrapesJs Content Templates Category');
        self::assertNotEmpty(
            $categories,
            'Content template categories "GrapesJs Content Templates Category" not found'
        );

        $expectedCategories = $table->getColumn(0);
        foreach ($categories as $index => $eachCategory) {
            $categoryTitle = $this->createElement('GrapesJs Content Templates Category Title', $eachCategory);
            self::assertTrue(
                $categoryTitle->isIsset(),
                'Category title element "GrapesJs Content Templates Category Title" not found on page'
            );

            self::assertEquals(trim($expectedCategories[$index]), trim($categoryTitle->getText()));
        }
    }

    /**
     * @Then /^(?:|I )there are (?P<count>(?:\d+)) content templates? in category "(?P<category>(?:[^"]|\\")*)"$/
     */
    public function thereAreCountContentTemplatesInCategory(int $count, string $category): void
    {
        $categories = $this->findAllElements('GrapesJs Content Templates Category');
        self::assertNotEmpty(
            $categories,
            'Content template categories "GrapesJs Content Templates Category" not found'
        );

        $foundCategory = null;
        foreach ($categories as $eachCategory) {
            $categoryTitle = $this->createElement('GrapesJs Content Templates Category Title', $eachCategory);
            self::assertTrue(
                $categoryTitle->isIsset(),
                'Category title element "GrapesJs Content Templates Category Title" not found on page'
            );

            if (trim($category) === trim($categoryTitle->getText())) {
                $contentTemplates = $this->findAllElements('GrapesJs Content Template', $eachCategory);
                self::assertCount(
                    $count,
                    $contentTemplates,
                    sprintf(
                        '%d content templates expected in "%s" content template category, got %d',
                        $count,
                        $category,
                        count($contentTemplates)
                    )
                );

                return;
            }
        }

        self::assertNotNull($foundCategory, sprintf('Content template category "%s" not found', $category));
    }

    /**
     * @When /^(?:|I )drag and drop the block "(?P<blockElement>(?:[^"]|\\")*)" to the GrapesJs Wysiwyg Root Area$/
     */
    public function dragAndDropTheBlockToGrapesJs(string $blockElement): void
    {
        $sourceContextNodePath = '/';
        $element = $this->createElement($blockElement);
        self::assertTrue($element->isIsset(), sprintf('GrapesJs block "%s" not found on page', $blockElement));

        $destinationContextNode = $this->createElement('GrapesJs Wysiwyg');
        self::assertTrue($destinationContextNode->isIsset(), 'GrapesJs block "GrapesJs Wysiwyg" not found on page');

        $destination = $this->createElement('GrapesJs Wysiwyg Root Area');

        $snippet = file_get_contents(__DIR__ . '/Snippet/drag-and-drop.js');

        $this->getSession()
            ->getDriver()
            ->executeScript(
                sprintf(
                    '(%s)("%s", "%s", "%s", "%s");',
                    trim($snippet),
                    addslashes($element->getXPath()),
                    addslashes($destination->getXPath()),
                    addslashes($sourceContextNodePath),
                    addslashes($destinationContextNode->getXPath())
                )
            );
    }
}
